# SolidPro Engineering Services — Marketing Tracker Web App
## Full Product Requirements Document (PRD)

**Version:** 1.0  
**Prepared for:** Web App Development  
**Context:** SolidPro is a B2B engineering services firm operating across 10 verticals, 6 geographies, and 8 GTM channels. The marketing team of ~14 manages campaigns across Digital Transformation, Industrial Goods & Consumer Products, Structural Engineering, MedTech, Energy & Utilities, Sustainability, BFSI, Product Innovation, and SolidPro AI. This tracker replaces a two-file Excel system and becomes the single source of truth for all marketing execution.

---

## Table of Contents

1. [Product Overview](#1-product-overview)
2. [User Roles & Permissions](#2-user-roles--permissions)
3. [Campaign Naming Convention](#3-campaign-naming-convention)
4. [Data Architecture & Entities](#4-data-architecture--entities)
5. [Module 1 — Campaign Master](#5-module-1--campaign-master)
6. [Module 2 — Creatives (Graphic Design & Video)](#6-module-2--creatives-graphic-design--video)
7. [Module 3 — Landing Pages](#7-module-3--landing-pages)
8. [Module 4 — Content Writing](#8-module-4--content-writing)
9. [Module 5 — Lead Magnets](#9-module-5--lead-magnets)
10. [Module 6 — Email Sequences](#10-module-6--email-sequences)
11. [Module 7 — Ad Copy](#11-module-7--ad-copy)
12. [Module 8 — SEO Tasks](#12-module-8--seo-tasks)
13. [Module 9 — Webinars & Events](#13-module-9--webinars--events)
14. [Approval Workflow](#14-approval-workflow)
15. [Dashboard & Reporting](#15-dashboard--reporting)
16. [Notifications & Alerts](#16-notifications--alerts)
17. [Global UI/UX Requirements](#17-global-uiux-requirements)
18. [Field Reference — Dropdown Values](#18-field-reference--dropdown-values)
19. [Asset Linking Logic](#19-asset-linking-logic)
20. [Future Considerations](#20-future-considerations)

---

## 1. Product Overview

### 1.1 Purpose
A centralised web-based marketing operations tracker for SolidPro's marketing team to plan, execute, track, approve, and report on all marketing assets across campaigns — replacing fragmented Excel files with a structured, role-aware, relational system.

### 1.2 Core Design Principles
- **Campaign-first architecture:** Every asset belongs to a campaign. No asset exists without a Campaign ID.
- **Relational linking:** Assets reference each other (e.g. a Landing Page links to its Creative, Lead Magnet, and Email Sequence IDs).
- **Two-gate approval:** Every asset goes through Project Head approval → Manager approval before publishing.
- **Auto-generated IDs:** Campaign IDs and Asset IDs are system-generated based on naming convention rules — never manually typed.
- **Role-based access:** Different team members see and edit only what is relevant to their function.
- **Status-driven workflow:** Every asset moves through a defined lifecycle with status transitions driving visibility and notifications.

### 1.3 Scope
The system covers 9 asset modules under a single Campaign Master:

| Module | Asset Code | Primary Owner |
|---|---|---|
| Creatives (Graphic Design & Video) | GD / VD / MG | Designer (Sathish), Video freelancers |
| Landing Pages | LP | Dev (SS), Copywriter (Santhosh) |
| Content Writing | CW | Copywriter (Santhosh) |
| Lead Magnets | LM | Copywriter + Designer |
| Email Sequences | EM | Copywriter (Santhosh) |
| Ad Copy | AD | Marketing Lead (Ananth) + Copywriter |
| SEO Tasks | SEO | SEO Owner (TBD) |
| Webinars & Events | EV | GTM Lead (Selva) + Marketing Lead |
| Campaign Master | CAMP | Marketing Lead (Ananth) |

---

## 2. User Roles & Permissions

### 2.1 Role Definitions

| Role | Who | Access Level |
|---|---|---|
| **Super Admin** | Marketing Lead (Ananth) | Full access — create, edit, delete, approve, view all modules and all verticals |
| **Project Head** | BU Heads (Darshan, Parthiban, Subramanian, Prasana, Uthayanan, Sudhagar P, Ramprasath) | View all assets in their vertical; First-gate approval only; Cannot edit asset details |
| **Manager** | Marketing Manager | View all assets across all verticals; Second-gate approval; Cannot create campaigns |
| **Content Creator** | Santhosh (Copywriter) | Create and edit: Content Writing, Email Sequences, Landing Page copy fields, Lead Magnet copy; Cannot approve |
| **Designer** | Sathish (Graphic Design) | Create and edit: Creatives module; View-only on other modules |
| **Developer** | SS (Web Dev) | Create and edit: Landing Pages (technical fields); View-only on other modules |
| **GTM / Events** | Selva | Create and edit: Webinars & Events, Campaign Master (read); View-only elsewhere |
| **Coordinator** | Nandhini | View-only across all modules; Can add comments/notes |
| **Freelancer** | Video freelancers | Create and edit: Creatives (Video asset type only); Cannot see other modules |

### 2.2 Permission Matrix

| Action | Super Admin | Project Head | Manager | Content Creator | Designer | Developer | GTM | Coordinator | Freelancer |
|---|---|---|---|---|---|---|---|---|---|
| Create Campaign | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Edit Campaign | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Create Asset (any) | ✅ | ❌ | ❌ | Partial | Partial | Partial | Partial | ❌ | Partial |
| Edit Own Asset | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Edit Others' Asset | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Gate 1 Approval | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Gate 2 Approval | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Delete Asset | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View Dashboard | ✅ | Vertical only | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Export Data | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### 2.3 Vertical-Based Scoping
- Project Heads are scoped to their vertical only. A MedTech BU Head cannot see Digital Transformation campaigns.
- Super Admin and Manager see all verticals.
- All creators see only campaigns/assets assigned to them regardless of vertical.

---

## 3. Campaign Naming Convention

### 3.1 Campaign ID Format
```
[VERTICAL-CODE]-[GOAL-CODE]-[DESCRIPTOR]-[MON][YY]
```

**Examples:**
```
DT-LG-PLM-LAUNCH-APR25
IG-BD-VALVE-WHITEPAPER-JUN25
SE-TL-BRIDGE-CASESTUDY-Q3-25
MT-EV-MEDTECH-SUMMIT-SEP25
EU-NR-SOLAR-NURTURE-OCT25
```

### 3.2 Vertical Codes

| Vertical | Code |
|---|---|
| Digital Transformation | DT |
| Industrial Goods & Consumer Products | IG |
| Structural Engineering | SE |
| MedTech | MT |
| Energy & Utilities | EU |
| Sustainability | SUS |
| BFSI | BFSI |
| Product Innovation | PI |
| SolidPro AI | AI |
| Other / Cross-Vertical | XX |

### 3.3 Goal Codes

| Goal | Code |
|---|---|
| Lead Generation | LG |
| Brand / Demand Generation | BD |
| Thought Leadership | TL |
| Event / Webinar | EV |
| Nurture / Retention | NR |
| Product Launch | PL |
| ABM (Account-Based Marketing) | AB |
| Re-engagement | RE |

### 3.4 Descriptor Rules
- 2–3 words maximum, hyphen-separated
- All uppercase
- No generic names (CAMPAIGN1, TEST, MISC are not allowed)
- Should describe the campaign offer or topic: PLM-LAUNCH, VALVE-GUIDE, BRIDGE-STUDY

### 3.5 Month Format
- 3-letter abbreviation: JAN FEB MAR APR MAY JUN JUL AUG SEP OCT NOV DEC
- Year: 2-digit (25, 26)
- Quarter alternative allowed: Q1-25, Q2-25, Q3-25, Q4-25

### 3.6 Asset ID Format
```
[CAMPAIGN-ID]-[ASSET-CODE]-[SEQ]
```

**Examples:**
```
DT-LG-PLM-LAUNCH-APR25-GD-001   ← Graphic Design asset #1
DT-LG-PLM-LAUNCH-APR25-LP-001   ← Landing Page #1
DT-LG-PLM-LAUNCH-APR25-LM-001   ← Lead Magnet #1
DT-LG-PLM-LAUNCH-APR25-EM-001   ← Email #1 in sequence
DT-LG-PLM-LAUNCH-APR25-CW-001   ← Content Writing piece #1
```

### 3.7 System Behaviour
- Campaign ID is **auto-generated** by the system when a campaign is created, based on the user's selected Vertical, Goal Code, entered Descriptor, and current month/year.
- The user confirms or edits the auto-generated ID before saving.
- Asset IDs are **fully auto-generated** — no user input required. The system appends asset code and sequential number automatically.
- IDs are **immutable** once created. If a campaign is renamed, the ID does not change.
- The system must **validate uniqueness** of Campaign IDs and reject duplicates.

---

## 4. Data Architecture & Entities

### 4.1 Entity Relationship Overview

```
Campaign (1)
  ├── Creatives (many)
  ├── Landing Pages (many)
  │     └── links to → Creatives, Lead Magnets, Email Sequences
  ├── Content Writing (many)
  │     └── links to → Landing Pages, Lead Magnets
  ├── Lead Magnets (many)
  │     └── links to → Landing Pages, Content Writing
  ├── Email Sequences (many)
  │     └── links to → Lead Magnets, Landing Pages
  ├── Ad Copy (many)
  │     └── links to → Creatives
  ├── SEO Tasks (many)
  │     └── links to → Content Writing, Landing Pages
  └── Webinars & Events (many)
        └── links to → Landing Pages, Lead Magnets, Creatives
```

### 4.2 Linking Rules
- All asset-to-asset links are optional (not mandatory) but strongly encouraged
- Links are stored as foreign keys / asset IDs
- When viewing an asset, linked assets must be visible as clickable references that open the linked record
- Deleting a linked asset must show a warning listing all assets that reference it

### 4.3 Core Shared Fields (present in every asset module)

| Field | Type | Required | Notes |
|---|---|---|---|
| Asset ID | String (auto) | Yes | System-generated, immutable |
| Campaign ID | Lookup / FK | Yes | Must exist in Campaign Master |
| Vertical | Dropdown | Yes | Auto-populated from Campaign |
| Asset Name / Title | Text | Yes | Human-readable name |
| Owner | User lookup | Yes | Assigned team member |
| Brief Date | Date | No | When brief was received |
| Due Date | Date | Yes | Internal completion deadline |
| Publish / Go-Live Date | Date | Yes | Scheduled publication date |
| Priority | Dropdown | Yes | Critical / High / Medium / Low |
| Status | Dropdown | Yes | See status lifecycle below |
| Approved by Project Head | Dropdown | Yes | Pending / Approved / Changes Requested / Rejected |
| Approved by Manager | Dropdown | Yes | Pending / Approved / Changes Requested / Rejected |
| Revision # | Integer (auto) | Yes | Increments each time status returns to In Revision |
| Feedback / Notes | Long text | No | Internal comments |
| Archived | Boolean | Yes | Default: No |
| Created By | User (auto) | Yes | System-logged |
| Created At | Timestamp (auto) | Yes | System-logged |
| Last Modified By | User (auto) | Yes | System-logged |
| Last Modified At | Timestamp (auto) | Yes | System-logged |

### 4.4 Asset Status Lifecycle

```
Briefed → In Progress → In Revision ↔ (loop)
                ↓
    Submitted for PH Approval
                ↓
    Approved by Project Head  ←→  Changes Requested (returns to In Revision)
                ↓
    Submitted for Manager Approval
                ↓
    Approved by Manager  ←→  Changes Requested (returns to In Revision)
                ↓
    Published / Live
                ↓
    Archived
```

**Additional terminal states (reachable from any stage):**
- On Hold — paused indefinitely, reason required
- Cancelled — removed from active tracking, reason required

**Rules:**
- Status can only move forward through the lifecycle by the asset owner
- Approval statuses can only be changed by the designated approver role
- Moving to "Published / Live" requires both approval gates to be "Approved"
- System must block "Published / Live" if either approval is not "Approved"
- Revision # increments automatically each time an asset returns to "In Revision"

---

## 5. Module 1 — Campaign Master

### 5.1 Purpose
The Campaign Master is the top-level entity. Every asset in every module must belong to a campaign. Creating a campaign is the mandatory first step before any assets can be created.

### 5.2 Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Campaign ID | String (auto) | Yes | Auto-generated per naming convention |
| Campaign Name | Text | Yes | Human-readable, used in ID generation |
| Vertical | Dropdown | Yes | Determines vertical code in ID |
| Goal Code | Dropdown | Yes | Determines goal code in ID |
| Descriptor | Text | Yes | 2–3 words, used in ID generation |
| Campaign Type | Dropdown | Yes | See dropdown list |
| Campaign Goal | Dropdown | Yes | Primary conversion goal |
| Target Audience | Text | No | Persona or segment description |
| Geography | Multi-select | No | Regions / countries targeted |
| Campaign Start Date | Date | Yes | |
| Campaign End Date | Date | Yes | Must be after start date |
| Go-Live Date | Date | Yes | First asset publish date |
| Priority | Dropdown | Yes | |
| Campaign Owner | User lookup | Yes | Typically Marketing Lead |
| Campaign Status | Dropdown | Yes | Planning / Active / Completed / Paused / Cancelled |
| Approved by Project Head | Dropdown | Yes | |
| Approved by Manager | Dropdown | Yes | |
| Total Assets | Integer (computed) | — | Count of all linked assets across all modules |
| Brief / Overview | Long text | No | Campaign strategy summary |

### 5.3 Computed Fields
- **Total Assets:** Real-time count of all non-archived assets linked to this Campaign ID across all 8 asset modules
- **Assets by Module:** Breakdown count per module (Creatives: 3, Landing Pages: 1, etc.)
- **Completion %:** Percentage of assets in "Published / Live" or "Archived" status out of total

### 5.4 Campaign List View
- Columns: Campaign ID, Campaign Name, Vertical, Type, Go-Live Date, Priority, Status, Owner, Total Assets, Completion %
- Filters: Vertical, Status, Priority, Owner, Date range
- Sort: By Go-Live Date (default), Campaign Name, Priority, Status
- Search: Full-text across Campaign ID and Campaign Name

---

## 6. Module 2 — Creatives (Graphic Design & Video)

### 6.1 Purpose
Tracks all graphic design and video assets — social posts, banners, video reels, explainer videos, presentation decks, event materials, and all visual content.

### 6.2 Additional Fields (beyond core shared fields)

| Field | Type | Required | Notes |
|---|---|---|---|
| Content Type | Dropdown | Yes | Social Post, Blog Visual, Case Study Visual, etc. |
| Asset Type | Dropdown | Yes | Graphic Design / Video / Motion Graphic / Animation / Illustration / Template |
| Format / Size | Text | No | e.g. 1080x1080, 16:9, A4 |
| Platform | Multi-select | Yes | LinkedIn, Instagram, YouTube, etc. |
| Brief / Description | Long text | Yes | Creative brief |
| Support (Copywriter) | User lookup | No | If copy assistance needed |
| Requested By | User lookup | No | Who initiated the request |
| Final File Link | URL | No | Google Drive / Dropbox link to final files |

### 6.3 Asset Type → Asset Code Mapping (for ID generation)

| Asset Type | Code used in Asset ID |
|---|---|
| Graphic Design | GD |
| Video | VD |
| Motion Graphic | MG |
| Animation | AN |
| Photography Edit | PE |
| Illustration | IL |
| Template | TP |

---

## 7. Module 3 — Landing Pages

### 7.1 Purpose
Tracks all campaign landing pages from brief through design, development, QA, and go-live. Each landing page is a distinct URL tied to a campaign.

### 7.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Landing Page Type | Dropdown | Yes | Lead Gen, Campaign, Product, Event, Webinar, Resource, Assessment, etc. |
| Page Goal | Dropdown | Yes | Lead Capture, Demo Request, Content Download, etc. |
| Page URL / Slug | Text | No | Final URL or proposed slug |
| CTA Text | Text | Yes | Primary call-to-action button text |
| Form Fields Required | Text | No | List of form fields e.g. Name, Email, Company, Role |
| SEO Title | Text | No | Meta title (60 chars max) |
| Meta Description | Text | No | Meta description (155 chars max) |
| Dev Owner | User lookup | Yes | SS or developer responsible |
| Content Writer | User lookup | No | Copywriter for page copy |
| Linked Creative ID(s) | Asset lookup (multi) | No | Creatives used on this page |
| Linked Lead Magnet ID | Asset lookup | No | Lead magnet offered on this page |
| Linked Email Sequence ID | Asset lookup | No | Sequence triggered by this page's form |
| UTM Parameters | Text | No | Full UTM string for tracking |
| A/B Test | Dropdown | No | No / Yes – Planned / Yes – Active / Yes – Concluded |
| Conversion Goal | Dropdown | No | What counts as a conversion |
| Final URL | URL | No | Live page URL once published |

---

## 8. Module 4 — Content Writing

### 8.1 Purpose
Tracks all written content assets — blog posts, case studies, whitepapers, website copy, email scripts, video scripts, and any other written deliverable.

### 8.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Content Format | Dropdown | Yes | Blog Post, Case Study, Whitepaper, eBook, Landing Page Copy, Email Copy, Script (Video), etc. |
| Topic / Angle | Text | Yes | Working title or angle |
| Target Keyword(s) | Text | No | Primary and secondary SEO keywords |
| Word Count (Target) | Integer | No | Target word count |
| Audience Persona | Text | No | Who this is written for |
| Funnel Stage | Dropdown | Yes | TOFU / MOFU / BOFU / Retention |
| Tone of Voice | Dropdown | Yes | Technical & Authoritative / Consultative / Conversational / Inspirational / Formal |
| Linked LP ID | Asset lookup | No | Landing page this content supports |
| Linked Lead Magnet ID | Asset lookup | No | Lead magnet this content promotes |
| References / Resources | Text | No | Source URLs, existing case studies to reference |
| Content Writer | User lookup | Yes | Assigned writer |
| Reviewer | User lookup | No | SME or BU head who reviews for accuracy |
| Draft Link | URL | No | Google Docs or similar link to draft |
| SEO Optimised | Dropdown | No | No / Yes – Partial / Yes – Full |
| CMS Published | Dropdown | No | No / Yes – Scheduled / Yes – Published |

---

## 9. Module 5 — Lead Magnets

### 9.1 Purpose
Tracks all gated and ungated offers — downloadable PDFs, assessments, quizzes, calculators, checklists, templates, and any asset used to capture or qualify leads.

### 9.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Lead Magnet Type | Dropdown | Yes | Downloadable PDF, Assessment / Quiz, Checklist, Template, Calculator Tool, eBook, Infographic Pack, Webinar Recording, Research Report, ROI Calculator, Comparison Guide, Other |
| Topic / Theme | Text | Yes | What the lead magnet covers |
| Format / Delivery | Dropdown | Yes | PDF, Interactive Web, Google Form, Typeform, HubSpot Form, Excel / Sheet, Video, Other |
| Target Persona | Text | No | Who this is designed for |
| Funnel Stage | Dropdown | Yes | TOFU / MOFU / BOFU |
| Linked LP ID | Asset lookup | No | Landing page that hosts this lead magnet |
| Linked Content ID | Asset lookup | No | Content piece that promotes this lead magnet |
| Number of Pages / Questions | Integer | No | For PDFs: pages. For assessments: number of questions |
| Content Writer | User lookup | No | |
| Designer | User lookup | No | |
| Gating | Dropdown | Yes | Yes – Gated / No – Ungated / Partially Gated |
| Form Fields | Text | No | Fields required to access the asset |
| Lead Data Destination | Dropdown | Yes | HubSpot / Zoho CRM / Google Sheet / Email Notification / Other |
| Download / Access Link | URL | No | Final hosted URL |
| Results / Score Defined | Dropdown | No | For assessments — Yes / No / In Progress |

---

## 10. Module 6 — Email Sequences

### 10.1 Purpose
Tracks individual emails within nurture sequences, drip campaigns, event follow-ups, and newsletters. Each email in a sequence is a separate row, enabling granular status tracking per email.

### 10.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Sequence Name | Text | Yes | Name of the overall sequence e.g. "PLM Lead Nurture – Apr25" |
| Email # in Sequence | Integer | Yes | Position in sequence: 1, 2, 3… |
| Email Subject Line | Text | Yes | The actual subject line |
| Preview Text | Text | No | Email preview / pre-header text |
| Email Type | Dropdown | Yes | Welcome / Nurture / Promotional / Event Invite / Follow-Up / Re-engagement / Newsletter / Transactional |
| Trigger / Entry Point | Text | Yes | What triggers this email e.g. "Form fill on LP-001" / "Day 3 after download" |
| Audience Segment | Text | No | Which segment receives this email |
| Send Day / Delay | Text | Yes | e.g. "Immediately", "Day 3", "Day 7 after trigger" |
| CTA in Email | Text | No | CTA button text |
| CTA URL | URL | No | CTA destination |
| Linked Lead Magnet ID | Asset lookup | No | LM that triggered this sequence |
| Linked LP ID | Asset lookup | No | LP the email drives traffic to |
| ESP / Tool | Dropdown | Yes | HubSpot / Mailchimp / Zoho Campaigns / SendGrid / ActiveCampaign / Other |
| Template Used | Text | No | Email template name or ID in ESP |
| Open Rate Target (%) | Decimal | No | Target benchmark |
| Click Rate Target (%) | Decimal | No | Target benchmark |

### 10.3 Sequence View
- The system should offer a **Sequence View** in addition to the standard table view
- Sequence View groups all emails by Sequence Name and displays them in a horizontal or vertical card flow ordered by Email # in Sequence
- Each card shows: Email #, Subject Line, Send Delay, Status, Owner

---

## 11. Module 7 — Ad Copy

### 11.1 Purpose
Tracks all paid advertising copy across LinkedIn Ads, Google, Meta, and other platforms. Each ad variation is a separate row.

### 11.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Ad Platform | Dropdown | Yes | LinkedIn Ads / Google Search / Google Display / Meta Ads / Twitter/X Ads / YouTube Ads / Programmatic / Other |
| Ad Format | Dropdown | Yes | Single Image / Carousel / Video Ad / Lead Gen Form / Sponsored Content / Text Ad / Responsive Display / Other |
| Ad Objective | Dropdown | Yes | Brand Awareness / Lead Generation / Traffic / Engagement / Conversions / Retargeting / Other |
| Headline 1 | Text | Yes | Primary headline (30 chars max for Google) |
| Headline 2 | Text | No | Secondary headline |
| Body Copy | Long text | Yes | Main ad body text |
| CTA Text | Text | Yes | Call-to-action on ad |
| Destination URL | URL | Yes | Landing page URL |
| Linked Creative ID | Asset lookup | No | Visual creative paired with this copy |
| Audience Targeting | Text | No | Target audience description |
| Budget (INR) | Currency | No | Allocated budget for this ad |
| Run Start Date | Date | No | Campaign flight start |
| Run End Date | Date | No | Campaign flight end |
| UTM Parameters | Text | No | Full UTM string |
| Ad Account / Campaign ID | Text | No | ID in the ad platform |

---

## 12. Module 8 — SEO Tasks

### 12.1 Purpose
Tracks all SEO work tied to campaigns — keyword research, on-page optimisation, technical audits, content optimisation, and link building.

### 12.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| SEO Task Type | Dropdown | Yes | Keyword Research / On-Page Optimisation / Technical SEO / Content Optimisation / Link Building / Competitor Analysis / Schema Markup / Site Audit / Other |
| Target Page / URL | URL | No | Page being optimised |
| Primary Keyword | Text | Yes | Main target keyword |
| Secondary Keywords | Text | No | Supporting keywords, comma-separated |
| Current Ranking | Integer | No | Current SERP position |
| Target Ranking | Integer | No | Goal SERP position |
| Monthly Search Volume | Integer | No | MSV from tool |
| Keyword Difficulty | Integer | No | KD score 0–100 |
| On-Page Changes | Long text | No | Description of changes to be made |
| Backlink Target | Text | No | Target domains for link acquisition |
| Linked Content ID | Asset lookup | No | Content piece being optimised |
| Linked LP ID | Asset lookup | No | Landing page being optimised |
| Tool Used | Dropdown | No | Ahrefs / SEMrush / Google Search Console / Moz / Screaming Frog / Ubersuggest / Other |
| Tracking Implemented | Dropdown | No | No / Yes – GA4 / Yes – Search Console / Yes – Both |

---

## 13. Module 9 — Webinars & Events

### 13.1 Purpose
Tracks all webinars, virtual events, in-person events, roundtables, workshops, and podcasts from planning through post-event follow-up.

### 13.2 Additional Fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Event Type | Dropdown | Yes | Webinar / Virtual Event / In-Person Event / Hybrid Event / Workshop / Roundtable / Conference / Podcast / Trade Show / Other |
| Event Format | Dropdown | Yes | Live / Pre-recorded / On-Demand / Hybrid |
| Topic / Agenda | Long text | Yes | Session topic and agenda outline |
| Speaker(s) | Text | Yes | Names and designations |
| Target Audience | Text | No | Persona or segment |
| Registration Link | URL | No | Registration / sign-up URL |
| Platform / Tool | Dropdown | Yes | Zoom / Microsoft Teams / Google Meet / Hopin / Airmeet / LinkedIn Live / YouTube Live / Other |
| Event Date | Date | Yes | |
| Event Time | Text | No | e.g. 3:00 PM IST |
| Duration (mins) | Integer | No | Session duration |
| Linked LP ID | Asset lookup | No | Registration landing page |
| Linked Lead Magnet ID | Asset lookup | No | Resource offered to attendees |
| Promotion Asset IDs | Asset lookup (multi) | No | Creatives used to promote the event |
| Expected Attendees | Integer | No | Registration target |
| Actual Attendees | Integer | No | Filled post-event |
| Recording Link | URL | No | Filled post-event |
| Follow-Up Email Sent | Dropdown | No | No / Yes – Sent / Scheduled |

---

## 14. Approval Workflow

### 14.1 Two-Gate Model

Every asset in every module follows the same approval path:

```
Owner submits → Gate 1: Project Head → Gate 2: Manager → Published
```

### 14.2 Approval States

| State | Who can set it | Description |
|---|---|---|
| ⏳ Pending | System (default) | Approval not yet requested |
| ✅ Approved | Designated approver only | Asset cleared to proceed |
| 🔁 Changes Requested | Designated approver only | Asset returned with comments |
| ❌ Rejected | Designated approver only | Asset rejected, reason required |

### 14.3 Rules
- An asset cannot move to "Approved by Manager" unless "Approved by Project Head" is already "Approved"
- An asset cannot be marked "Published / Live" unless both gates are "Approved"
- The system must enforce this sequence programmatically — not just via UI guidance
- When an approver selects "Changes Requested" or "Rejected", the system must prompt for a mandatory comment before saving
- That comment is logged in the asset's activity history and triggers a notification to the asset owner
- Approvers receive a notification (in-app + email) when an asset is submitted to them for review
- The asset owner receives a notification when an approval decision is made

### 14.4 Approval History
- Every approval action must be stored with: approver name, decision, timestamp, and comment (if any)
- This history is viewable on each asset record and cannot be edited or deleted

---

## 15. Dashboard & Reporting

### 15.1 Dashboard Views

#### Super Admin / Manager Dashboard
- **Campaign Health:** Total campaigns by status (Planning / Active / Completed / Paused / Cancelled)
- **Asset Pipeline:** Total assets across all modules by status
- **Vertical Breakdown:** Asset count by vertical
- **Overdue Assets:** Assets where Due Date has passed and Status is not Published/Archived
- **Pending Approvals:** Assets awaiting approval (Gate 1 or Gate 2) with days waiting
- **Module Breakdown:** Asset count per module (Creatives, LP, Content, LM, Email, Ad Copy, SEO, Events)
- **Priority Heatmap:** Assets by Priority × Status matrix
- **This Week's Due Dates:** Assets due in the next 7 days

#### Project Head Dashboard
- Scoped to their vertical only
- Campaign status for their vertical's campaigns
- Assets pending their approval (Gate 1)
- Asset pipeline for their vertical

#### Creator Dashboard (personalised)
- My Assets: All assets assigned to me, filtered by status
- My Overdue: Assets past due date
- My Pending Approvals: Assets I've submitted that are awaiting approval

### 15.2 Filters (global across all list views)
- Vertical (multi-select)
- Campaign ID / Name (search)
- Asset Type / Module
- Status (multi-select)
- Priority (multi-select)
- Owner (user lookup)
- Date range (Due Date / Publish Date / Brief Date)
- Archived (toggle — default: hide archived)

### 15.3 Export
- Super Admin and Manager can export any module's data as CSV or Excel
- Export respects active filters
- Export includes all fields including system-generated timestamps

---

## 16. Notifications & Alerts

### 16.1 In-App Notifications
All users receive a notification bell in the header. Notifications are real-time.

### 16.2 Email Notifications
Sent for all critical workflow events. Users can configure frequency (immediate / daily digest).

### 16.3 Notification Events

| Event | Who is notified |
|---|---|
| Asset created and assigned to owner | Asset owner |
| Asset submitted for Gate 1 approval | Project Head (scoped to vertical) |
| Asset submitted for Gate 2 approval | Manager |
| Gate 1: Approved | Asset owner |
| Gate 1: Changes Requested | Asset owner |
| Gate 1: Rejected | Asset owner |
| Gate 2: Approved | Asset owner |
| Gate 2: Changes Requested | Asset owner |
| Gate 2: Rejected | Asset owner |
| Asset is overdue (Due Date passed, not Published) | Asset owner + Super Admin |
| Campaign Go-Live Date is tomorrow | Campaign Owner + Super Admin |
| Asset status changed to Published / Live | Campaign Owner + Super Admin |
| Asset marked On Hold | Campaign Owner |
| Comment added to asset | Asset owner + mentioned users |

### 16.4 Overdue Logic
- An asset is flagged overdue when: `Due Date < Today` AND `Status NOT IN [Published / Live, Archived, Cancelled]`
- Overdue assets are highlighted in red on all list and dashboard views
- A daily digest email of all overdue assets is sent to Super Admin at 9:00 AM

---

## 17. Global UI/UX Requirements

### 17.1 Navigation Structure
```
Sidebar Navigation:
├── 🏠 Dashboard
├── 🗂 Campaigns
├── 🎨 Creatives
├── 🌐 Landing Pages
├── ✍ Content Writing
├── 🎁 Lead Magnets
├── 📧 Email Sequences
├── 📣 Ad Copy
├── 🔍 SEO
├── 🎤 Webinars & Events
├── 📊 Reports
└── ⚙️ Settings
    ├── Users & Roles
    ├── Verticals & Codes
    ├── Notification Preferences
    └── Naming Convention Reference
```

### 17.2 List View (all modules)
- Default view for every module
- Columns: configurable, with sensible defaults per module
- Pagination: 25 / 50 / 100 rows per page
- Sticky header row
- Sortable columns (click to sort asc/desc)
- Inline editing for Status, Priority, Owner (click to edit without opening full record)
- Row click → opens asset detail panel (side drawer, not a new page)
- Colour coding: status badges are colour-coded (Green = Approved/Live, Red = Cancelled/Rejected, Amber = On Hold/In Revision, Blue = In Progress, Grey = Briefed)

### 17.3 Asset Detail View (side drawer or full page)
- All fields in grouped sections (e.g. "Basic Info", "Dates & Priority", "Ownership", "Linked Assets", "Approval", "Activity Log")
- Linked assets shown as clickable chips that open the linked record
- Activity Log at the bottom: chronological list of all changes, comments, and approval actions with user and timestamp
- Comment box at the bottom for team communication (mentions supported with @username)
- Edit button — only visible to users with edit permission

### 17.4 Campaign Detail View
- Full campaign overview with all linked assets listed by module
- Each module section shows: asset count, completion %, list of assets with status badges
- Timeline view: all assets plotted on a horizontal timeline by Due Date and Publish Date
- Quick-add asset button per module section

### 17.5 Colour System for Status Badges
| Status | Background | Text |
|---|---|---|
| Briefed | #EBF5FB | #1A5276 |
| In Progress | #FEF9E7 | #7D6608 |
| In Revision | #FDEBD0 | #A04000 |
| Approved by PH | #D5F5E3 | #1E8449 |
| Approved by Manager | #D5F5E3 | #1E8449 |
| Published / Live | #D5F5E3 | #1E8449 |
| On Hold | #FDEBD0 | #A04000 |
| Cancelled | #FADBD8 | #C0392B |
| Overdue (overlay) | #FADBD8 | #C0392B |

### 17.6 Mobile Responsiveness
- Full functionality must be available on tablet (iPad-level)
- Mobile (phone): read-only list view and notifications; editing on mobile is out of scope for v1
- Approval actions (approve / reject) must work on mobile

### 17.7 Performance Requirements
- Page load: under 2 seconds for list views with up to 500 rows
- Search: results appear within 500ms of typing
- No full-page reloads for status updates or inline edits

---

## 18. Field Reference — Dropdown Values

### Verticals
Digital Transformation, Industrial Goods & Consumer Products, Structural Engineering, MedTech, Energy & Utilities, Sustainability, BFSI, Product Innovation, SolidPro AI, Other

### Campaign Types
Brand Awareness, Lead Generation, Demand Generation, Product Launch, Event Promotion, Thought Leadership, Nurture / Retention, ABM, Other

### Campaign Goals
Lead Capture, Demo Request, Content Download, Event Registration, Product Enquiry, Newsletter Signup, Brand Recall, Pipeline Generation, Other

### Campaign Status
🗓 Planning, 🚀 Active, ✅ Completed, ⏸ Paused, ❌ Cancelled

### Priority
🔴 Critical, 🟠 High, 🟡 Medium, 🟢 Low

### Approval Status
⏳ Pending, ✅ Approved, 🔁 Changes Requested, ❌ Rejected

### Asset Status (all modules)
📋 Briefed, ✍ In Progress, 🔁 In Revision, ✅ Approved by Project Head, ✅ Approved by Manager, 🚀 Published / Live, ⏸ On Hold, ❌ Cancelled

### Creative — Content Types
Social Media Post, Blog Visual, Case Study Visual, Whitepaper Cover, Email Banner, Ad Creative, Brochure / Flyer, Presentation Deck, Event Material, Video – Explainer, Video – Testimonial, Video – Reel/Short, Video – Webinar, Infographic, Thumbnail, Other

### Creative — Asset Types
Graphic Design, Video, Motion Graphic, Animation, Photography Edit, Illustration, Template, Other

### Platforms
LinkedIn, Instagram, Twitter/X, Facebook, YouTube, Website, Email, WhatsApp, Print, Multiple, Other

### Content Formats
Blog Post, Long-Form Article, Case Study, Whitepaper, eBook, LinkedIn Article, Website Copy, Landing Page Copy, Email Copy, Script (Video), Script (Podcast), FAQs, Product Description, Press Release, Other

### Funnel Stages
TOFU – Awareness, MOFU – Consideration, BOFU – Decision, Retention

### Tone of Voice
Technical & Authoritative, Consultative, Conversational, Inspirational, Formal

### Lead Magnet Types
Downloadable PDF, Assessment / Quiz, Checklist, Template, Calculator Tool, eBook, Infographic Pack, Webinar Recording, Research Report, ROI Calculator, Comparison Guide, Other

### Lead Magnet Delivery Formats
PDF, Interactive Web, Google Form, Typeform, HubSpot Form, Excel / Sheet, Video, Other

### Lead Data Destinations
HubSpot, Zoho CRM, Google Sheet, Email Notification Only, Other

### Email Types
Welcome, Nurture, Promotional, Event Invite, Follow-Up, Re-engagement, Newsletter, Transactional, Other

### ESP / Tools
HubSpot, Mailchimp, Zoho Campaigns, SendGrid, ActiveCampaign, Other

### Ad Platforms
LinkedIn Ads, Google Search, Google Display, Meta Ads, Twitter/X Ads, YouTube Ads, Programmatic, Other

### Ad Formats
Single Image, Carousel, Video Ad, Lead Gen Form, Sponsored Content, Text Ad, Responsive Display, Other

### Ad Objectives
Brand Awareness, Lead Generation, Traffic, Engagement, Conversions, Retargeting, Other

### SEO Task Types
Keyword Research, On-Page Optimisation, Technical SEO, Content Optimisation, Link Building, Competitor Analysis, Schema Markup, Site Audit, Other

### SEO Tools
Ahrefs, SEMrush, Google Search Console, Moz, Screaming Frog, Ubersuggest, Other

### Event Types
Webinar, Virtual Event, In-Person Event, Hybrid Event, Workshop, Roundtable, Conference, Podcast, Trade Show, Other

### Event Formats
Live, Pre-recorded, On-Demand, Hybrid

### Event Platforms
Zoom, Microsoft Teams, Google Meet, Hopin, Airmeet, LinkedIn Live, YouTube Live, Other

### Landing Page Types
Lead Gen, Campaign, Product / Service, Event, Webinar, Resource Download, Assessment, Free Trial, Contact, Other

### Geography Options
India, United States, United Kingdom, UAE / Middle East, Europe, APAC, Global, Other

---

## 19. Asset Linking Logic

### 19.1 How a Typical Campaign Flows

The following example illustrates how assets across modules are linked under a single campaign:

```
Campaign: DT-LG-PLM-LAUNCH-APR25
│
├── CREATIVE: DT-LG-PLM-LAUNCH-APR25-GD-001
│   └── LinkedIn Banner (1200x628) for social promotion
│
├── CREATIVE: DT-LG-PLM-LAUNCH-APR25-VD-001
│   └── 60-second explainer video
│
├── LANDING PAGE: DT-LG-PLM-LAUNCH-APR25-LP-001
│   ├── Linked Creative ID → GD-001 (banner displayed on page)
│   ├── Linked Lead Magnet ID → LM-001 (PDF offered via form)
│   └── Linked Email Sequence ID → EM-001 (triggered on form fill)
│
├── CONTENT WRITING: DT-LG-PLM-LAUNCH-APR25-CW-001
│   ├── Blog post: "Top 5 PLM Challenges in Manufacturing"
│   └── Linked LP ID → LP-001 (CTA in blog drives to landing page)
│
├── LEAD MAGNET: DT-LG-PLM-LAUNCH-APR25-LM-001
│   ├── Downloadable PDF: "PLM Buyer's Guide 2025"
│   ├── Linked LP ID → LP-001
│   └── Lead Data → HubSpot
│
├── EMAIL SEQUENCE: DT-LG-PLM-LAUNCH-APR25-EM-001 (Email #1)
│   ├── Welcome email post-download
│   ├── Trigger: Form fill on LP-001
│   └── Linked Lead Magnet ID → LM-001
│
├── EMAIL SEQUENCE: DT-LG-PLM-LAUNCH-APR25-EM-002 (Email #2)
│   └── Day 3 nurture email with case study link → CW-001
│
├── AD COPY: DT-LG-PLM-LAUNCH-APR25-AD-001
│   ├── LinkedIn Sponsored Content
│   ├── Linked Creative ID → GD-001
│   └── Destination URL → LP-001
│
└── SEO: DT-LG-PLM-LAUNCH-APR25-SEO-001
    ├── On-Page Optimisation for CW-001
    └── Linked Content ID → CW-001
```

### 19.2 Link Display in UI
- When viewing any asset, its "Linked Assets" section shows all assets it is linked to as clickable chips
- Chips display: Asset ID + Asset Name + Status badge
- Clicking a chip opens that asset's detail view in a side drawer without losing context

### 19.3 Orphan Detection
- Assets with no Campaign ID assigned are flagged as "Unlinked" and appear in a system warning
- The system must prevent saving an asset without a valid Campaign ID

---

## 20. Future Considerations

These are out of scope for v1 but should be architected for in the data model:

### 20.1 CRM Integration
- HubSpot or Zoho CRM integration to sync lead magnet form fills directly into the tracker as "leads generated" per campaign
- Campaign-level lead count visible in Campaign Master

### 20.2 Analytics Integration
- Google Analytics 4 / Search Console integration to pull actual performance data (page views, conversions, organic rankings) against campaign assets
- Actual vs target metrics for Landing Pages (conversion rate) and Email Sequences (open rate, CTR)

### 20.3 Asset Storage / DAM
- Direct file upload within the tracker (currently relies on external links to Google Drive)
- Version control for creative files — v1, v2, final, final-final
- Preview thumbnails for uploaded images and PDFs

### 20.4 Content Calendar View
- A calendar view plotting all assets by Publish / Go-Live Date
- Drag-to-reschedule functionality
- Colour-coded by vertical or module

### 20.5 Budget Tracking
- Campaign-level budget allocation field
- Per-asset cost input (design hours, ad spend, freelancer fees)
- Campaign spend vs budget dashboard widget

### 20.6 Template Library
- Reusable brief templates per asset type
- "Clone Campaign" — duplicate a campaign's structure (without asset data) for repeating campaign types

### 20.7 Approval SLA Tracking
- Track how long assets sit in each approval gate
- Flag approvals pending for more than X days (configurable per role)
- SLA breach notifications to Super Admin

---

*Document prepared based on SolidPro marketing operations context. All field names, dropdown values, naming conventions, and workflows reflect the team structure and vertical portfolio as of April 2025. Update version number and date when making structural changes.*
