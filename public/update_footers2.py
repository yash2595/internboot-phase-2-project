import os
import glob
import re

for ext in ['*.html', '*.php']:
    for filepath in glob.glob(ext):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        new_content = content
        
        # Remove Dashboard link
        new_content = re.sub(r'[ \t]*<li>\s*<a href="dashboard\.html"[^>]*>Dashboard</a>\s*</li>[ \t]*\r?\n?', '', new_content, flags=re.IGNORECASE)
        
        # Replace Privacy Policy link
        new_content = re.sub(r'<a href="[^"]*">Privacy Policy</a>', '<a href="privacy.html">Privacy Policy</a>', new_content, flags=re.IGNORECASE)
        
        # Replace Terms of Service link
        new_content = re.sub(r'<a href="[^"]*">Terms of Service</a>', '<a href="terms.html">Terms of Service</a>', new_content, flags=re.IGNORECASE)
        
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
