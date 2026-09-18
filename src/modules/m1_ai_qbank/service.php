<?php
// Path: src/modules/m1_ai_qbank/service.php

require_once __DIR__ . '/queries.php';

/**
 * Service logic to manually add an approved question and its 4 options inside a transaction.
 */
function add_manual_question(int $qbankId, string $questionText, string $difficulty, array $options, mysqli $conn, string $approvalStatus = 'pending'): array {
    // 1. Validate Question Bank exists
    $stmt = $conn->prepare("SELECT id FROM question_banks WHERE id = ?");
    $stmt->bind_param("i", $qbankId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception("Question Bank not found.");
    }
    $stmt->close();

    // 2. Validate exactly 1 correct option exists
    $correctCount = 0;
    foreach ($options as $opt) {
        if (!isset($opt['option_text']) || trim($opt['option_text']) === '') {
            throw new Exception("Option text cannot be empty.");
        }
        if (!empty($opt['is_correct'])) {
            $correctCount++;
        }
    }

    if ($correctCount !== 1) {
        throw new Exception("Exactly 1 option must be marked as correct (is_correct = 1).");
    }

    // 3. Begin Atomic Database Transaction
    $conn->begin_transaction();

    try {
        // Insert question record
        $questionId = insert_question($qbankId, $questionText, $difficulty, $conn, $approvalStatus);

        // Insert 4 option records
        foreach ($options as $opt) {
            $isCorrect = !empty($opt['is_correct']) ? 1 : 0;
            insert_question_option($questionId, trim($opt['option_text']), $isCorrect, $conn);
        }

        // Commit Transaction
        $conn->commit();

        return [
            'question_id' => $questionId,
            'question_bank_id' => $qbankId,
            'question_text' => $questionText,
            'difficulty' => $difficulty,
            'approval_status' => $approvalStatus,
        ];
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Transaction Failed: " . $e->getMessage());
    }
}

/**
 * Service logic to fetch all approved questions with options for a given question bank.
 * Security Note: $isAdmin flag controls whether the `is_correct` answer key is included.
 */
function fetch_approved_qbank_questions(int $qbankId, mysqli $conn, bool $isAdmin = false): array {
    $questions = get_approved_questions_by_qbank($qbankId, $conn);

    $formattedQuestions = [];
    foreach ($questions as $q) {
        $questionId = (int)$q['id'];
        $options = get_options_by_question_id($questionId, $conn);

        // Map options data safely
        $mappedOptions = array_map(function($opt) use ($isAdmin) {
            $optionData = [
                'id' => (int)$opt['id'],
                'option_text' => $opt['option_text']
            ];

            // Only expose answer key if requested by Admin session
            if ($isAdmin) {
                $optionData['is_correct'] = (int)$opt['is_correct'];
            }

            return $optionData;
        }, $options);

        $formattedQuestions[] = [
            'id' => $questionId,
            'question_text' => $q['question_text'],
            'difficulty' => $q['difficulty'],
            'options' => $mappedOptions
        ];
    }

    return [
        'question_bank_id' => $qbankId,
        'total_questions' => count($formattedQuestions),
        'questions' => $formattedQuestions
    ];
}
?>
