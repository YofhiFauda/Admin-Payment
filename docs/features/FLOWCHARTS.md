# WHUSNET Admin Payment - Flowcharts

This document provides a comprehensive overview of the system workflows, including reimbursement (Rembush), purchase requests (Pengajuan), branch allocation, and the AI-powered background OCR processing.

---

## 1. System Overview Flowchart
This flowchart describes the high-level lifecycle of a transaction from submission to completion.

```mermaid
graph TD
    Start((Start)) --> Submission[Teknisi Submission]
    Submission --> Type{Transaction Type?}
    
    Type -- Rembush --> OCR_Process[Background OCR Processing]
    Type -- Pengajuan --> Admin_Check[Admin/Atasan Review]
    Type -- Gudang --> Admin_Check
    
    OCR_Process --> OCR_Result{OCR Result?}
    OCR_Result -- Success/Low Conf --> Admin_Check
    OCR_Result -- Auto-Reject --> Override{Admin Override?}
    
    Override -- No --> End_Reject((Rejected))
    Override -- Yes --> Admin_Check
    
    Admin_Check --> Approval{Approved?}
    Approval -- No --> End_Reject
    Approval -- Yes --> Payment[Waiting for Payment]
    
    Payment --> Pay_Method{Payment Method?}
    
    Pay_Method -- Cash --> Cash_Flow[Admin Upload Handover Photo]
    Pay_Method -- Transfer --> Transfer_Flow[Admin Upload Transfer Proof]
    
    Cash_Flow --> Is_Gudang_Cash{Is Gudang?}
    Is_Gudang_Cash -- Yes --> End_Success
    Is_Gudang_Cash -- No --> Teknisi_Confirm{Teknisi Confirm?}
    
    Transfer_Flow --> Is_Gudang_Trf{Is Gudang?}
    Is_Gudang_Trf -- Yes --> End_Success
    Is_Gudang_Trf -- No --> Transfer_OCR[AI Transfer Verification]
    Transfer_OCR -- Match --> End_Success
    Transfer_OCR -- Mismatch --> Flagged[Flagged - Manual Review]
    
    Flagged --> Force_Approve{Force Approve?}
    Force_Approve -- Yes --> End_Success
```

---

## 2. Rembush Flowchart (with OCR Integration)
Detailed flow for reimbursements, integrating the multi-layer OCR verification logic.

```mermaid
graph TD
    subgraph Submission
        A[Teknisi Uploads Nota] --> B[Select Branch & Payment Method]
        B --> C[Submit to System]
    end

    subgraph Background_OCR_n8n
        C --> L1[Layer 1: Security - Image Hashing]
        L1 -- Duplicate Found --> L1_Reject[Reject: Duplicate Nota]
        L1 -- Unique --> L2[Layer 2: Logic - Date Validation]
        
        L2 -- Date > 2 Days Old --> L2_AutoReject[Status: AUTO-REJECT]
        L2 -- Date Valid --> L3[Layer 3: AI Extraction - Gemini]
        
        L3 --> L3_Result[Extract: Material, Qty, Price, Vendor, Date]
        L3_Result --> L3_Conf{Confidence >= 70%?}
        L3_Conf -- No --> L3_Low[Status: Low Confidence]
        L3_Conf -- Yes --> L3_High[Status: High Confidence]
    end

    subgraph Approval_Workflow
        L2_AutoReject --> Admin_Review
        L3_Low --> Admin_Review
        L3_High --> Admin_Review
        
        Admin_Review{Admin/Atasan Decision}
        Admin_Review -- Reject --> Rejected[Status: Rejected]
        Admin_Review -- Approve --> Threshold{Amount >= 1,000,000?}
        
        Threshold -- Yes --> Owner_Review[Owner Approval Required]
        Threshold -- No --> Ready_Payment[Status: Waiting for Payment]
        
        Owner_Review -- Approve --> Ready_Payment
        Owner_Review -- Reject --> Rejected
    end
```

---

## 3. Pengajuan Flowchart (Purchase Request)
Detailed flow for purchase requests featuring the Dual-Version System and role-based edit protections.

```mermaid
graph TD
    %% Roles
    subgraph Roles
        T[Teknisi]
        A[Admin]
        M[Management: Owner/Atasan]
    end

    %% Initiation
    Start((Start)) -->|Submit Request| T_Store[Teknisi Submits Pengajuan]
    T_Store -->|Status: Pending| Pending{Status: Pending}
    T_Store -->|Freeze Snapshot| Snapshot[Store items_snapshot<br/>Original Version]

    %% Interaction & Revision Logic
    Pending -->|View Detail| T_View[Teknisi: View Own Detail]
    Pending -->|View Detail| A_View[Admin: View Detail Modal]
    Pending -->|Edit Page| A_Edit[Admin: Read-Only Access]
    A_Edit -->|Comparison| A_Comp[View Original vs Management Version]

    Pending -->|View Detail| M_View[Management: View Detail Modal]
    Pending -->|Edit Page| M_Edit[Management: Full Edit Access]
    
    M_Edit -->|Update Items| M_Update{Is First Edit?}
    M_Update -->|Yes| M_Mark[Set is_edited_by_management = true<br/>Save Management Version]
    M_Update -->|No| M_Inc[Increment revision_count]
    M_Mark --> Pending
    M_Inc --> Pending

    %% Detail Modal Capabilities (All Roles)
    T_View & A_View & M_View --> Toggle[Toggle Version Button]
    Toggle -->|Switch| ViewOrig[View Versi Pengaju<br/>Yellow Indicators on Edited Fields]
    Toggle -->|Switch| ViewMgmt[View Versi Management<br/>Current/Edited Data]

    %% Approval Workflow
    Pending -->|Approve| M_Appr{Reviewer Role?}
    M_Appr -->|Atasan/Owner| WaitPay[Status: Waiting Payment]
    
    Pending -->|Reject| Rej[Status: Rejected]

    %% Finalization
    WaitPay -->|Confirm Payment| Comp[Status: Completed<br/>FINAL STATE]
    Comp -->|Edit Protection| Locked[Edit Page & Buttons Disabled<br/>ALL ROLES]
```

---

## 4. Branch Allocation Flowchart
Describes how transaction costs are distributed among different branches.

```mermaid
graph TD
    Start[Total Transaction Amount] --> Method_Selection{Select Allocation Method}
    
    Method_Selection -- Equal --> Equal_Calc[Amount / Count of Branches]
    Method_Selection -- Percentage --> Percent_Calc[Amount * Percent_Input / 100]
    Method_Selection -- Manual --> Manual_Calc[Direct Nominal Input]
    
    Equal_Calc --> Validate[Validate Total Sum == 100%]
    Percent_Calc --> Validate
    Manual_Calc --> Validate_Manual[Validate Total Sum == Amount]
    
    Validate --> Store[Store in transaction_branch Pivot Table]
    Validate_Manual --> Store
    
    Store --> Fields[Save: branch_id, allocation_percent, allocation_amount]
```

---

## 5. Background OCR Processing Flowchart
Detailed logic executed by n8n as per `OCR_Nota_Kontan_v4.5.json`.

```mermaid
flowchart TD
    WH[Webhook: Upload Nota] --> Extract[Extract binary & metadata]
    Extract --> Accept[Respond 202 Accepted]
    
    subgraph Layer_1_Security
        Accept --> Hash[Compute 3 Image Hashes: Exact, Boundary, Visual]
        Hash --> Redis_Check{Check Redis for Hashes}
        Redis_Check -- Match Found --> Dup[Mark as Duplicate]
        Redis_Check -- No Match --> Unique[Mark as Unique]
    end
    
    Unique --> Store_Redis[Store current hashes in Redis]
    
    subgraph Layer_2_Logic
        Store_Redis --> Date_Ext[Extract Date & Current Time]
        Date_Ext --> Date_Diff{Date Difference > 2 Days?}
        Date_Diff -- Yes --> Auto_Reject[Callback: AUTO-REJECT]
        Date_Diff -- No --> Layer_3
    end
    
    subgraph Layer_3_AI
        Layer_3[Gemini Full OCR] --> Gemini_Parse[Parse JSON Response]
        Gemini_Parse --> Conf_Check{Overall Confidence >= 70%?}
        
        Conf_Check -- No --> Low_Conf[Callback: Success - Low Confidence]
        Conf_Check -- Yes --> High_Conf[Callback: Success - High Confidence]
    end
    
    subgraph Payment_Verification
        Upload_Proof[Webhook: Upload Transfer Proof] --> Transfer_OCR[Gemini Transfer OCR]
        Transfer_OCR --> Parse_Nominal[Extract Nominal, Admin, Unique Code]
        Parse_Nominal --> Compare{Actual Total vs Expected Total}
        
        Compare -- Match +/- 1000 --> Match[Callback: MATCH - Selesai]
        Compare -- Mismatch --> Flagged[Callback: MISMATCH - Flagged]
    end
```

---

## 6. Gudang Flowchart (Internal Warehouse)
Simplified flow for internal warehouse expenditures that bypasses external verification requirements.

```mermaid
graph TD
    subgraph Submission
        A[Staff Internal: Admin/Owner] -->|Input| B[Isi Form Belanja Gudang]
        B -->|Submit| C[Status: Pending]
    end

    subgraph Approval
        C -->|Management Review| D{Management Decision}
        D -- Reject --> E[Status: Rejected]
        D -- Approve --> F[Status: Waiting Payment]
    end

    subgraph Payment
        F -->|Select Method| G{Cash / Transfer}
        G -->|Upload Proof| H[Process Proof Upload]
        H -->|Internal Staff Workflow| I[Bypass OCR / Telegram]
        I -->|Status Update| J[Status: Completed]
    end
```
