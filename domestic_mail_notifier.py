import pyodbc
import requests
import msal
import os

# ---------------- SQL Server connection ----------------
server = 'DESKTOP-IGPDORH'
database = 'Vraman_Adesh_Generator'
username = 'sa'
password = 'Lazy-Car92'

conn_str = f"DRIVER={{ODBC Driver 17 for SQL Server}};SERVER={server};DATABASE={database};UID={username};PWD={password}"
conn = pyodbc.connect(conn_str)
cursor = conn.cursor()

# === OAuth Configuration for Outlook/Office 365 ===
CLIENT_ID = os.getenv("OAUTH_CLIENT_ID", "45f45c02-2b3e-432d-a1fd-0980f50f6d59")
CLIENT_SECRET = os.getenv("OAUTH_CLIENT_SECRET", "")
TENANT_ID = os.getenv("OAUTH_TENANT_ID", "4ee6b0fa-3bdc-4fa2-8cd8-9ece2266c058")
SENDER_EMAIL = "hr@sebon.gov.np"  # Must match mailbox in Azure

AUTHORITY = f'https://login.microsoftonline.com/{TENANT_ID}'
SCOPE = ['https://graph.microsoft.com/.default']

app = msal.ConfidentialClientApplication(
    CLIENT_ID, authority=AUTHORITY, client_credential=CLIENT_SECRET
)
result = app.acquire_token_for_client(scopes=SCOPE)
access_token = result['access_token']

GRAPH_API_ENDPOINT = 'https://graph.microsoft.com/v1.0'

# ---------------- Step 1: Get pending batches for Domestic TADA ----------------
batch_query = """
SELECT  [Queue_id]
      ,[Batch_id]
      ,[SourceTable]
      ,[mailSentFlag]
      ,[created_date]
  FROM [Vraman_Adesh_Generator].[dbo].[BatchMailQueue]
  where  [SourceTable]='Domestic' and
  [mailSentFlag]=0
"""

cursor.execute(batch_query)
batches = cursor.fetchall()

print(f"Found {len(batches)} pending domestic batch(es) to process...")

for batch in batches:
    batch_id = batch.Batch_id
    print(f"\nProcessing Domestic Batch ID: {batch_id}")

    emp_query = """
    SELECT 
        dt.domestic_tada_id,
        dt.domestic_Batch_id,
        dt.domestic_Chalani_id,
        dt.domestic_form_date,
        dt.EmpPersonalCode,
        e.EmpName,
        e.Gender,
        e.Email,
        e.Designation,
        e.LevelName,
        dt.District_id,
        dm.District_name,
        CASE 
            WHEN dt.domestic_isTwentyPercentExtra = 1 THEN 'Yes'
            ELSE 'No'
        END AS isTwentyPercentExtra,
        dt.domestic_travel_objective,
        dt.domestic_travelDateStart,
        dt.domestic_travelDateEnd,
        dt.domestic_totalday,
        dt.domestic_tada,
        dtd.DomesticTadaDefinerMasterBylevel_name,
        dtd.tadaInNepali,
        dt.domestic_createddate,
        u.username as created_by_name
    FROM DomesticTada dt
    LEFT JOIN Employee_Information e ON dt.EmpPersonalCode = e.EmpPersonalCode
    LEFT JOIN DistrictMaster dm ON dt.District_id = dm.District_id
    LEFT JOIN DomesticTadaDefinerMasterBylevel dtd ON e.LevelName = dtd.DomesticTadaDefinerMasterBylevel_name
    LEFT JOIN Users u ON dt.domestic_createdBy = u.user_id
    WHERE dt.domestic_Batch_id = ?
    """

    cursor.execute(emp_query, batch_id)
    employees = cursor.fetchall()
    
    print(f"Found {len(employees)} employee(s) in Domestic Batch {batch_id}")

    for emp in employees:
        salutation = "Sir" if emp.Gender == "Male" else "Ma'am"

        email_body = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: 'Times New Roman', Times, serif; margin: 0; padding: 0; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="650" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <!-- Header -->
                    
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="font-family: 'Times New Roman', Times, serif; font-size: 15px; margin: 0 0 20px 0; color: #2c3e50;">Dear {emp.EmpName} {salutation},</p>
                            
                            <!-- Note Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px; border-radius: 5px;">
                                        <p style="font-family: 'Times New Roman', Times, serif; margin: 0; font-size: 14px; color: #155724;">Your Travel Details are: </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Data Table -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="1" style="margin: 25px 0; border-collapse: collapse; border: 1px solid #dee2e6;">
                                <!-- Employee Information -->
                                <tr>
                                    <td colspan="2" style="background-color: #28a745; color: #ffffff; padding: 12px 14px; font-family: 'Times New Roman', Times, serif; font-size: 16px; font-weight: normal; border: 1px solid #28a745;">👤 Employee Information</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #ffffff; border: 1px solid #dee2e6;">Designation</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #ffffff; border: 1px solid #dee2e6;">{emp.Designation}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #f8f9fa; border: 1px solid #dee2e6;">Level</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #f8f9fa; border: 1px solid #dee2e6;">{emp.LevelName}</td>
                                </tr>
                                
                                <!-- Travel Information -->
                                <tr>
                                    <td colspan="2" style="background-color: #28a745; color: #ffffff; padding: 12px 14px; font-family: 'Times New Roman', Times, serif; font-size: 16px; font-weight: normal; border: 1px solid #28a745;">✈️ Travel Information</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #ffffff; border: 1px solid #dee2e6;">District</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #ffffff; border: 1px solid #dee2e6;">{emp.District_name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #f8f9fa; border: 1px solid #dee2e6;">Travel Objective</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #f8f9fa; border: 1px solid #dee2e6;">{emp.domestic_travel_objective}</td>
                                </tr>
                                
                                <!-- Duration & Allowances -->
                                <tr>
                                    <td colspan="2" style="background-color: #28a745; color: #ffffff; padding: 12px 14px; font-family: 'Times New Roman', Times, serif; font-size: 16px; font-weight: normal; border: 1px solid #28a745;">📅 Duration & Allowances</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #ffffff; border: 1px solid #dee2e6;">Travel Start Date</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #ffffff; border: 1px solid #dee2e6;">{emp.domestic_travelDateStart}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #f8f9fa; border: 1px solid #dee2e6;">Travel End Date</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #f8f9fa; border: 1px solid #dee2e6;">{emp.domestic_travelDateEnd}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #ffffff; border: 1px solid #dee2e6;">Total Days</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #ffffff; border: 1px solid #dee2e6;">{emp.domestic_totalday} days</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #f8f9fa; border: 1px solid #dee2e6;">Daily TADA Rate</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #f8f9fa; border: 1px solid #dee2e6;">NPR {emp.tadaInNepali:,.2f}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #ffffff; border: 1px solid #dee2e6;">Total TADA Amount</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 16px; color: #28a745; background-color: #ffffff; border: 1px solid #dee2e6;">NPR {emp.domestic_tada:,.2f}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #495057; width: 45%; background-color: #f8f9fa; border: 1px solid #dee2e6;">20% Extra Allowance</td>
                                    <td style="padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; color: #2c3e50; background-color: #f8f9fa; border: 1px solid #dee2e6;">{emp.isTwentyPercentExtra}</td>
                                </tr>
                            </table>
                            
                            <p style="font-family: 'Times New Roman', Times, serif; margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;">This module has also been successfully integrated into the SEBON MIS (<a href="http://10.10.0.199/sebonmis/index.php" style="color: #28a745; text-decoration: none;">http://10.10.0.199/sebonmis/index.php</a>) under the 'भ्रमण आदेश' tab.</p>
                            
                            <p style="font-family: 'Times New Roman', Times, serif; margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;">
                                Regards,<br>
                                HR Section<br>
                                Securities Board of Nepal
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 3px solid #28a745;">
                            <p style="font-family: 'Times New Roman', Times, serif; margin: 0; font-size: 13px; color: #6c757d;"><em>** This is an automated mail generated from the application. Please do not reply to this email.</em></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>"""

        mail_payload = {
            "message": {
                "subject": f"Domestic Travel Notification - Batch {batch_id} | {emp.District_name}",
                "body": {
                    "contentType": "HTML",
                    "content": email_body
                },
                "toRecipients": [
                    {"emailAddress": {"address": emp.Email}}
                ]
            }
        }

        headers = {
            "Authorization": f"Bearer {access_token}",
            "Content-Type": "application/json"
        }

        response = requests.post(
            f"{GRAPH_API_ENDPOINT}/users/{SENDER_EMAIL}/sendMail",
            headers=headers,
            json=mail_payload
        )

        if response.status_code == 202:
            print(f"Email sent to {emp.Email}")
        else:
            print(f"Failed to send email to {emp.Email}: {response.text}")

    update_query = "UPDATE [BatchMailQueue] SET mailSentFlag = 1 WHERE Batch_id = ? AND SourceTable = 'Domestic'"
    cursor.execute(update_query, batch_id)
    conn.commit()
    print(f" Domestic Batch {batch_id} marked as complete\n")

cursor.close()
conn.close()

print("\n" + "="*50)
print("All domestic batches processed successfully!")
print("="*50)