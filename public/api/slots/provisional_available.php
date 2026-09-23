<?php
// VERIFICATION_TOKEN: VERIFY-25BCE14D1F630DEA
// Path: public/api/slots/provisional_available.php
require_once __DIR__ . '/../../../src/core/bootstrap.php';
require_once __DIR__ . '/../../../src/modules/m5_batch_slots/controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json_response('error', 'Only GET request method is allowed', null, 405);
}

handle_list_provisional_slots_request($_GET, $conn);
