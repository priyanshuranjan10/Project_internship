import pandas as pd
for file in ['data/Activities_Master_List.xlsx', 'data/Activities_Master_List_Customized.xlsx']:
    try:
        xls = pd.ExcelFile(file)
        df1 = pd.read_excel(file, sheet_name='Co-Curricular Activities', skiprows=2)
        df2 = pd.read_excel(file, sheet_name='Extra-Curricular Activities', skiprows=2)
        print(f"{file}: CC={len(df1.dropna(subset=['Activity ID']))}, EC={len(df2.dropna(subset=['Activity ID']))}")
    except Exception as e:
        print(f"Error reading {file}: {e}")
