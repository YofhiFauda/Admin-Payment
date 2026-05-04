
import re

file_path = r'd:\Whusnet\Testing Runnig Background\Admin-Payment\resources\js\transactions\modals.js'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Range 284-742 (1-indexed)
content = "".join(lines[283:742])

# Remove comments
content = re.sub(r"//.*", "", content)
content = re.sub(r"/\*.*?\*/", "", content, flags=re.DOTALL)

# Handle Optional Chaining ?.
# Replace ?. with a dummy string
content = re.sub(r'\?\.', '_OPT_', content)

# Handle Nullish Coalescing ??
# Count ?? and replace with dummy
nullish_count = len(re.findall(r'\?\?', content))
content = re.sub(r'\?\?', '_NULLISH_', content)

# Count ternary ?
ternary_count = len(re.findall(r'\?', content))

# Count if
if_count = len(re.findall(r'\bif\b', content))

# Count case
case_count = len(re.findall(r'\bcase\b', content))

# Count loops
loop_count = len(re.findall(r'\bfor\b', content)) + len(re.findall(r'\bwhile\b', content))

# Count logical operators
and_count = len(re.findall(r'&&', content))
or_count = len(re.findall(r'\|\|', content))

# Count method iterations
# map, forEach, some, every, filter, find
iter_count = len(re.findall(r'\.(map|forEach|some|every|filter|find)\(', content))

print(f"If: {if_count}")
print(f"Case: {case_count}")
print(f"Loop: {loop_count}")
print(f"Ternary: {ternary_count}")
print(f"And: {and_count}")
print(f"Or: {or_count}")
print(f"Nullish: {nullish_count}")
print(f"Iteration: {iter_count}")

total = if_count + case_count + loop_count + ternary_count + and_count + or_count + nullish_count + iter_count + 1
print(f"Total Complexity: {total}")
