import os
import glob
import re

for ext in ['*.html', '*.php']:
    for filepath in glob.glob(ext):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        new_content = content
        
        # Remove Dashboard link
        new_content = re.sub(r'\s*<li><a href="dashboard\.html">Dashboard</a></li>', '', new_content)
        
        # Replace Privacy Policy and Terms of Service links
        new_content = new_content.replace('<a href="#">Privacy Policy</a>', '<a href="privacy.html">Privacy Policy</a>')
        new_content = new_content.replace('<a href="#">Terms of Service</a>', '<a href="terms.html">Terms of Service</a>')
        
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
