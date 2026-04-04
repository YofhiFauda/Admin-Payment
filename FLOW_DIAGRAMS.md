# Dual Version System - Flow Diagrams

## 1. Data Flow Sequence

```mermaid
sequenceDiagram
    participant T as Teknisi
    participant S as System
    participant DB as Database
    participant O as Owner/Atasan
    
    Note over T,O: FASE 1: SUBMIT PENGAJUAN
    T->>S: Submit Pengajuan (items)
    S->>DB: Create Transaction
    activate DB
    DB-->>S: transaction.id
    deactivate DB
    S->>DB: Snapshot Original Data
    activate DB
    Note right of DB: items_snapshot = items<br/>is_edited = false
    DB-->>S: OK
    deactivate DB
    S-->>T: Pengajuan Berhasil
    
    Note over T,O: FASE 2: EDIT PERTAMA (OWNER)
    O->>S: Edit Pengajuan
    S->>DB: Check is_edited_by_management
    DB-->>S: false (belum pernah edit)
    S->>DB: Freeze Snapshot
    activate DB
    Note right of DB: items_snapshot = FREEZE<br/>items = data_baru<br/>is_edited = true<br/>edited_by = owner_id<br/>revision_count = 1
    DB-->>S: OK
    deactivate DB
    S-->>O: Update Berhasil
    S-->>T: Notifikasi: Pengajuan Direvisi
    
    Note over T,O: FASE 3: VIEW VERSI
    T->>S: View Pengajuan
    S->>DB: Load Data
    DB-->>S: items + items_snapshot
    S-->>T: Tampilkan Toggle Versi
    T->>S: Toggle ke Versi Asli
    S-->>T: Render items_snapshot (read-only)
    T->>S: Toggle ke Versi Management
    S-->>T: Render items (dengan highlight)
```

---

## 2. State Machine

```mermaid
stateDiagram-v2
    [*] --> Draft: Teknisi Create
    Draft --> Submitted: Submit Pengajuan
    
    state Submitted {
        [*] --> Original
        Original --> Original: View Only
        
        state "Versi Pengaju" as Original {
            items_snapshot: Data Asli
            editable: false
        }
    }
    
    Submitted --> FirstEdit: Owner/Atasan Edit
    
    state FirstEdit {
        [*] --> FreezeSnapshot
        FreezeSnapshot --> UpdateItems
        UpdateItems --> MarkEdited
        
        state MarkEdited {
            is_edited: true
            edited_by: owner_id
            revision_count: 1
        }
    }
    
    FirstEdit --> Revised
    
    state Revised {
        [*] --> DualVersion
        
        state DualVersion {
            state fork_state <<fork>>
            [*] --> fork_state
            fork_state --> VOriginal: Toggle
            fork_state --> VManagement: Toggle
            
            state "Versi Pengaju (Frozen)" as VOriginal {
                source: items_snapshot
                editable: false
                badge: none
            }
            
            state "Versi Management" as VManagement {
                source: items
                editable: true (Owner/Atasan)
                badge: changes highlighted
            }
            
            VOriginal --> fork_state
            VManagement --> fork_state
        }
    }
    
    Revised --> SubsequentEdit: Edit Lagi
    
    state SubsequentEdit {
        [*] --> KeepSnapshot
        KeepSnapshot --> UpdateItems2
        UpdateItems2 --> IncrementRevision
        
        state KeepSnapshot {
            items_snapshot: UNCHANGED
        }
        
        state IncrementRevision {
            revision_count: +1
        }
    }
    
    SubsequentEdit --> Revised
    
    Revised --> [*]: Approved/Completed
```

---

## 3. Component Architecture

```mermaid
graph TB
    subgraph "Frontend Layer"
        A[Edit Pengajuan Page]
        B[Version Switcher Component]
        C[Item Cards Container]
        D[Change Badges]
        E[Field Highlights]
    end
    
    subgraph "JavaScript Layer"
        F[Version Toggle Handler]
        G[Render Version Function]
        H[Mark Changes Function]
        I[Compare Items Logic]
    end
    
    subgraph "Backend Layer"
        J[TransactionController]
        K[Transaction Model]
        L[Helper Methods]
    end
    
    subgraph "Database Layer"
        M[(transactions table)]
        N[items column]
        O[items_snapshot column]
        P[versioning metadata]
    end
    
    A --> B
    B --> F
    A --> C
    C --> G
    G --> H
    H --> D
    H --> E
    F --> I
    I --> L
    
    J --> K
    K --> L
    L --> M
    M --> N
    M --> O
    M --> P
    
    F -.JSON Data.-> L
    G -.Fetch.-> K
```

---

## 4. Data Structure

```mermaid
erDiagram
    TRANSACTIONS {
        bigint id PK
        json items "Versi Management (current)"
        json items_snapshot "Versi Pengaju (frozen)"
        boolean is_edited_by_management
        bigint edited_by FK
        timestamp edited_at
        int revision_count
    }
    
    USERS {
        bigint id PK
        string name
        enum role
    }
    
    ITEM_CHANGES {
        int index "Virtual - computed"
        string type "added|modified|removed"
        json original "From items_snapshot"
        json current "From items"
        json fields "Changed fields detail"
    }
    
    TRANSACTIONS ||--o| USERS : "edited_by"
    TRANSACTIONS ||--o{ ITEM_CHANGES : "getItemChanges()"
```

---

## 5. UI State Flow

```mermaid
stateDiagram-v2
    [*] --> CheckEdited: Load Page
    
    CheckEdited --> NoVersion: is_edited = false
    CheckEdited --> ShowToggle: is_edited = true
    
    NoVersion --> NormalEdit: Single Version Only
    
    ShowToggle --> OriginalActive: Default State
    
    state OriginalActive {
        [*] --> RenderOriginal
        RenderOriginal --> DisableInputs
        DisableInputs --> HideButtons
        
        state RenderOriginal {
            data_source: items_snapshot
            badge: none
        }
        
        state DisableInputs {
            all_inputs: disabled
            background: gray
            cursor: not-allowed
        }
        
        state HideButtons {
            add_button: hidden
            remove_button: hidden
        }
    }
    
    OriginalActive --> ManagementActive: Click Management Toggle
    
    state ManagementActive {
        [*] --> RenderManagement
        RenderManagement --> HighlightChanges
        HighlightChanges --> EnableInputs
        
        state RenderManagement {
            data_source: items
            compare_with: items_snapshot
        }
        
        state HighlightChanges {
            added: green_badge
            modified: yellow_badge
            removed: red_badge
            fields: border_highlight
        }
        
        state EnableInputs {
            inputs: editable
            buttons: visible
        }
    }
    
    ManagementActive --> OriginalActive: Click Original Toggle
    ManagementActive --> SaveChanges: Submit Form
    
    SaveChanges --> [*]
```

---

## 6. Change Detection Algorithm

```mermaid
flowchart TD
    A[Start: getItemChanges] --> B{Has Management Edit?}
    B -->|No| C[Return Empty Array]
    B -->|Yes| D[Get Original Items]
    
    D --> E[Get Current Items]
    E --> F[Loop Through Max Count]
    
    F --> G{Original Item Exists?}
    G -->|No| H{Current Item Exists?}
    H -->|Yes| I[Type: ADDED]
    H -->|No| F
    
    G -->|Yes| J{Current Item Exists?}
    J -->|No| K[Type: REMOVED]
    J -->|Yes| L[Compare Fields]
    
    L --> M{Fields Changed?}
    M -->|Yes| N[Type: MODIFIED]
    M -->|No| F
    
    I --> O[Add to Changes Array]
    K --> O
    N --> O
    
    O --> F
    F -->|Loop Done| P[Return Changes Array]
    P --> Q[End]
    
    C --> Q
```

---

## 7. Permission Matrix

```mermaid
graph LR
    subgraph "Teknisi"
        A1[View Original ✓]
        A2[View Management ✓]
        A3[Edit Original ✗]
        A4[Edit Management ✗]
    end
    
    subgraph "Admin"
        B1[View Original ✓]
        B2[View Management ✓]
        B3[Edit Original ✗]
        B4[Edit Management ✗]
    end
    
    subgraph "Atasan"
        C1[View Original ✓]
        C2[View Management ✓]
        C3[Edit Original ✗]
        C4[Edit Management ✓]
    end
    
    subgraph "Owner"
        D1[View Original ✓]
        D2[View Management ✓]
        D3[Edit Original ✗]
        D4[Edit Management ✓]
    end
    
    style A4 fill:#ffcccc
    style A3 fill:#ffcccc
    style B3 fill:#ffcccc
    style B4 fill:#ffcccc
    style C3 fill:#ffcccc
    style C4 fill:#ccffcc
    style D3 fill:#ffcccc
    style D4 fill:#ccffcc
```

---

**Legend:**
- ✓ = Allowed
- ✗ = Forbidden
- 🟢 Green = Editable
- 🔴 Red = Read-only
