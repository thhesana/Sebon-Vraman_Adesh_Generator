import pyodbc
import requests
import msal
import os

# ---------------- SQL Server connection ----------------
server = 'DESKTOP-IGPDORH'
database = 'Vraman_Adesh_Generator'
username = 'sa'
password = 'Lazy-Car92'

# Use ODBC Driver 17 (same as your working code)
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

# Get access token
app = msal.ConfidentialClientApplication(
    CLIENT_ID, authority=AUTHORITY, client_credential=CLIENT_SECRET
)
result = app.acquire_token_for_client(scopes=SCOPE)
access_token = result['access_token']

GRAPH_API_ENDPOINT = 'https://graph.microsoft.com/v1.0'

# ---------------- Step 1: Get pending batches ----------------
batch_query = """
SELECT  [Queue_id]
      ,[Batch_id]
      ,[SourceTable]
      ,[mailSentFlag]
      
  FROM [Vraman_Adesh_Generator].[dbo].[BatchMailQueue]
  where  [SourceTable]='International' and
  [mailSentFlag]=0
"""

cursor.execute(batch_query)
batches = cursor.fetchall()

# ---------------- Step 2: Loop through batches ----------------
print(f"Found {len(batches)} pending batch(es) to process...")

for batch in batches:
    batch_id = batch.Batch_id
    print(f"\nProcessing Batch ID: {batch_id}")

    # Fetch employees for this batch (using parameterized query to prevent SQL injection)
    emp_query = """
    SELECT 
        i.Batch_id,
        i.Chalani_id,
        i.form_date,
        i.EmpPersonalCode,
        emp.EmpName AS EmployeeName,
        emp.Gender,
        emp.Email,
        c.Country_name AS Country,
        ci.City_name AS City,
        i.travel_objective,
        i.travelDateStart,
        i.travelDateEnd,
        t.tadaInUSD,
        CAST(DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5 AS DECIMAL(5,2)) AS totalday,
        CASE 
            WHEN c.extra33percent_country = 1 THEN (t.tadaInUSD * 1.33) * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
            ELSE t.tadaInUSD * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
        END AS tadaInUSD_Final,
        CASE WHEN i.DressAllowance = 1 THEN 'Yes' ELSE 'No' END AS DressAllowance
    FROM International_tada i
    LEFT JOIN CountryMaster c ON i.Country_id = c.Country_id
    LEFT JOIN CityMaster ci ON i.City_id = ci.City_id
    LEFT JOIN TadaDefinerMasterBylevel t ON i.TadaDefinerMasterBylevel_id = t.TadaDefinerMasterBylevel_id
    LEFT JOIN Employee_Information emp ON i.EmpPersonalCode = emp.EmpPersonalCode
    WHERE i.Batch_id = ?
    """

    cursor.execute(emp_query, batch_id)
    employees = cursor.fetchall()
    
    print(f"Found {len(employees)} employee(s) in Batch {batch_id}")

    # ---------------- Step 3: Send email to each employee ----------------
    for emp in employees:
        salutation = "Sir" if emp.Gender == "Male" else "Ma'am"

        # HTML email with professional table layout
        email_body = f"""
        <html>
        <head>
            <style>
                body {{ font-family: "Times New Roman", Times, serif; line-height: 1.6; color: #333; }}
                .container {{ max-width: 600px; margin: 0 auto; padding: 20px; }}
                .header {{ background-color: #0066cc; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }}
                .content {{ background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }}
                table {{ width: 100%; border-collapse: collapse; margin: 20px 0; background-color: white; }}
                th {{ background-color: #0066cc; color: white; padding: 12px; text-align: left; font-weight: bold; }}
                td {{ padding: 10px; border-bottom: 1px solid #ddd; }}
                tr:hover {{ background-color: #f5f5f5; }}
                .label {{ font-weight: bold; color: #555; width: 40%; }}
                .footer {{ background-color: #f0f0f0; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }}
                .note {{ background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }}
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>International Travel Notification</h2>
                    
                </div>
                <div class="content">
                    <p>Dear  <strong>{emp.EmployeeName}</strong> {salutation},</p>
                    
                    <div class="note">
                    Your Travel Details are: 
                    </div>
                    
                    <table>
                        <tr>
                            <th colspan="2">Travel Information</th>
                        </tr>
                        <tr>
                            <td class="label">Country</td>
                            <td>{emp.Country}</td>
                        </tr>
                        <tr>
                            <td class="label">City</td>
                            <td>{emp.City}</td>
                        </tr>
                        <tr>
                            <td class="label">Travel Objective</td>
                            <td>{emp.travel_objective}</td>
                        </tr>
                        <tr>
                            <th colspan="2">Duration & Allowances</th>
                        </tr>
                        <tr>
                            <td class="label">Travel Start Date</td>
                            <td>{emp.travelDateStart}</td>
                        </tr>
                        <tr>
                            <td class="label">Travel End Date</td>
                            <td>{emp.travelDateEnd}</td>
                        </tr>
                        <tr>
                            <td class="label">Total Days</td>
                            <td><strong>{emp.totalday}</strong> days</td>
                        </tr>
                        <tr>
                            <td class="label">TADA Amount</td>
                            <td><strong style="color: #0066cc; font-size: 16px;">${emp.tadaInUSD_Final:.2f} USD</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Dress Allowance</td>
                            <td>{emp.DressAllowance}</td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 20px;">This module has also been successfully integrated into the SEBON MIS (http://10.10.0.199/sebonmis/index.php
) under the ‘भ्रमण आदेश’ tab.</p>
                    
                    <p style="margin-top: 20px;">
                        <strong>Regards,</strong><br>
                        HR Section<br>
                        Securities Board of Nepal
                    </p>
                </div>
                <div class="footer">
                    <p><em>** This is an automated mail generated from the application. Please do not reply to this email.</em></p>
                </div>
            </div>
        </body>
        </html>
        """

        mail_payload = {
            "message": {
                "subject": f"Travel Notification - Batch {batch_id} | {emp.Country}",
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

    # ---------------- Step 4: Update mailSentFlag (using parameterized query) ----------------
    update_query = "UPDATE [BatchMailQueue] SET mailSentFlag = 1 WHERE Batch_id = ?"
    cursor.execute(update_query, batch_id)
    conn.commit()
    print(f"✓ Batch {batch_id} marked as complete\n")

cursor.close()
conn.close()

print("\n" + "="*50)
print("All batches processed successfully!")
print("="*50)