import sys

def extract_method(filepath, method_name):
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
    
    inside_method = False
    brace_count = 0
    method_content = []
    
    for line in lines:
        if not inside_method:
            if method_name + "(" in line and ("void" in line or "String" in line or "ResultSet" in line):
                inside_method = True
                brace_count += line.count('{') - line.count('}')
                method_content.append(line)
        else:
            method_content.append(line)
            brace_count += line.count('{') - line.count('}')
            if brace_count == 0:
                break
    return ''.join(method_content)

print("----- alergi -----")
print(extract_method("E:/WORK/GITHUB_REPO/SIMRS-Khanza/KhanzaHMSServiceSatuSehat/src/khanzahmsservicesatusehat/frmUtama.java", "alergi")[:1000])

print("\n\n----- encounter2 -----")
print(extract_method("E:/WORK/GITHUB_REPO/SIMRS-Khanza/KhanzaHMSServiceSatuSehat/src/khanzahmsservicesatusehat/frmUtama.java", "encounter2")[:1000])

print("\n\n----- kirimdicomrouter -----")
print(extract_method("E:/WORK/GITHUB_REPO/SIMRS-Khanza/KhanzaHMSServiceSatuSehat/src/khanzahmsservicesatusehat/frmUtama.java", "kirimdicomrouter")[:1000])

print("\n\n----- qrtelaahresep -----")
print(extract_method("E:/WORK/GITHUB_REPO/SIMRS-Khanza/KhanzaHMSServiceSatuSehat/src/khanzahmsservicesatusehat/frmUtama.java", "qrtelaahresep")[:1000])