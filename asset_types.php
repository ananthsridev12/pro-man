<?php
$ASSET_TYPES = [
    'creative' => [
        'label'  => 'Creatives',
        'icon'   => 'fa-palette',
        'color'  => '#e74c3c',
        'prefix' => 'GD',
        'extra'  => [
            'content_type'   => ['label' => 'Content Type', 'options' => ['Social Media Post','Blog Visual','Case Study Visual','Whitepaper Cover','Email Banner','Ad Creative','Brochure / Flyer','Presentation Deck','Event Material','Video – Explainer','Video – Testimonial','Video – Reel/Short','Video – Webinar','Infographic','Thumbnail','Other']],
            'asset_type_det' => ['label' => 'Asset Type',   'options' => ['Graphic Design','Video','Motion Graphic','Animation','Photography Edit','Illustration','Template','Other']],
            'format_size'    => ['label' => 'Format / Size'],
            'platform'       => ['label' => 'Platform',     'options' => ['LinkedIn','Instagram','Twitter/X','Facebook','YouTube','Website','Email','WhatsApp','Print','Multiple','Other']],
            'campaign_theme' => ['label' => 'Campaign / Theme'],
            'brief_desc'     => ['label' => 'Brief / Description', 'textarea' => true],
        ]
    ],
    'landing_page' => [
        'label'  => 'Landing Pages',
        'icon'   => 'fa-globe',
        'color'  => '#2980b9',
        'prefix' => 'LP',
        'extra'  => [
            'lp_type'          => ['label' => 'Landing Page Type', 'options' => ['Lead Gen','Campaign','Product / Service','Event','Webinar','Resource Download','Assessment','Free Trial','Contact','Other']],
            'page_goal'        => ['label' => 'Page Goal',         'options' => ['Lead Capture','Demo Request','Content Download','Event Registration','Product Enquiry','Newsletter Signup','Brand Recall','Pipeline Generation','Other']],
            'page_url'         => ['label' => 'Page URL / Slug'],
            'cta_text'         => ['label' => 'CTA Text'],
            'form_fields'      => ['label' => 'Form Fields Required'],
            'seo_title'        => ['label' => 'SEO Title'],
            'meta_description' => ['label' => 'Meta Description'],
            'dev_owner'        => ['label' => 'Dev Owner', 'user' => true],
            'content_writer'   => ['label' => 'Content Writer', 'user' => true],
            'utm_params'       => ['label' => 'UTM Parameters'],
            'ab_test'          => ['label' => 'A/B Test?',         'options' => ['No','Yes – Planned','Yes – Active','Yes – Concluded']],
            'conversion_goal'  => ['label' => 'Conversion Goal',   'options' => ['Lead Capture','Demo Request','Content Download','Event Registration','Product Enquiry','Newsletter Signup','Other']],
            'linked_creative'  => ['label' => 'Linked Creative ID'],
            'linked_lm'        => ['label' => 'Linked Lead Magnet ID'],
            'linked_email_seq' => ['label' => 'Linked Email Seq ID'],
        ]
    ],
    'content_writing' => [
        'label'  => 'Content Writing',
        'icon'   => 'fa-pen-nib',
        'color'  => '#27ae60',
        'prefix' => 'CW',
        'extra'  => [
            'content_format'   => ['label' => 'Content Format',  'options' => ['Blog Post','Long-Form Article','Case Study','Whitepaper','eBook','LinkedIn Article','Website Copy','Landing Page Copy','Email Copy','Script (Video)','Script (Podcast)','FAQs','Product Description','Press Release','Other']],
            'topic'            => ['label' => 'Topic / Angle'],
            'target_keywords'  => ['label' => 'Target Keyword(s)'],
            'word_count'       => ['label' => 'Word Count (Target)', 'number' => true],
            'audience_persona' => ['label' => 'Audience Persona'],
            'funnel_stage'     => ['label' => 'Funnel Stage',    'options' => ['TOFU – Awareness','MOFU – Consideration','BOFU – Decision','Retention']],
            'tone_of_voice'    => ['label' => 'Tone of Voice',   'options' => ['Technical & Authoritative','Consultative','Conversational','Inspirational','Formal']],
            'content_writer'   => ['label' => 'Content Writer', 'user' => true],
            'draft_link'       => ['label' => 'Draft Link'],
            'seo_optimised'    => ['label' => 'SEO Optimised?',  'options' => ['No','Yes – Partial','Yes – Full']],
            'cms_published'    => ['label' => 'CMS Published?',  'options' => ['No','Yes – Scheduled','Yes – Published']],
            'linked_lp'        => ['label' => 'Linked Landing Page ID'],
        ]
    ],
    'lead_magnet' => [
        'label'  => 'Lead Magnets',
        'icon'   => 'fa-magnet',
        'color'  => '#8e44ad',
        'prefix' => 'LM',
        'extra'  => [
            'lm_type'         => ['label' => 'Lead Magnet Type',  'options' => ['Downloadable PDF','Assessment / Quiz','Checklist','Template','Calculator Tool','eBook','Infographic Pack','Webinar Recording','Research Report','ROI Calculator','Comparison Guide','Other']],
            'topic'           => ['label' => 'Topic / Theme'],
            'format'          => ['label' => 'Format / Delivery', 'options' => ['PDF','Interactive Web','Google Form','Typeform','HubSpot Form','Excel / Sheet','Video','Other']],
            'target_persona'  => ['label' => 'Target Persona'],
            'funnel_stage'    => ['label' => 'Funnel Stage',      'options' => ['TOFU – Awareness','MOFU – Consideration','BOFU – Decision']],
            'num_pages'       => ['label' => 'No. of Pages / Qs', 'number' => true],
            'gating'          => ['label' => 'Gating?',           'options' => ['Yes – Gated','No – Ungated','Partially Gated']],
            'lead_data_to'    => ['label' => 'Lead Data Goes To', 'options' => ['HubSpot','Zoho CRM','Google Sheet','Email Notification Only','Other']],
            'download_link'   => ['label' => 'Download / Access Link'],
            'results_defined' => ['label' => 'Results Defined?',  'options' => ['No','Yes','In Progress']],
            'content_writer'  => ['label' => 'Content Writer', 'user' => true],
            'designer'        => ['label' => 'Designer',       'user' => true],
            'linked_lp'       => ['label' => 'Linked Landing Page ID'],
            'linked_content'  => ['label' => 'Linked Content ID'],
        ]
    ],
    'email_sequence' => [
        'label'  => 'Email Sequences',
        'icon'   => 'fa-envelope',
        'color'  => '#d35400',
        'prefix' => 'EM',
        'extra'  => [
            'sequence_name'  => ['label' => 'Sequence Name'],
            'email_num'      => ['label' => 'Email # in Sequence', 'number' => true],
            'subject_line'   => ['label' => 'Email Subject Line'],
            'email_type'     => ['label' => 'Email Type',   'options' => ['Welcome','Nurture','Promotional','Event Invite','Follow-Up','Re-engagement','Newsletter','Transactional','Other']],
            'trigger'        => ['label' => 'Trigger / Entry Point'],
            'audience_seg'   => ['label' => 'Audience Segment'],
            'send_delay'     => ['label' => 'Send Day / Delay'],
            'cta_text'       => ['label' => 'CTA in Email'],
            'esp_tool'       => ['label' => 'ESP / Tool',    'options' => ['HubSpot','Mailchimp','Zoho Campaigns','SendGrid','ActiveCampaign','Other']],
            'template_used'  => ['label' => 'Template Used'],
            'open_rate_tgt'  => ['label' => 'Open Rate Target (%)'],
            'click_rate_tgt' => ['label' => 'Click Rate Target (%)'],
            'linked_lm'      => ['label' => 'Linked Lead Magnet ID'],
        ]
    ],
    'ad_copy' => [
        'label'  => 'Ad Copy',
        'icon'   => 'fa-bullhorn',
        'color'  => '#c0392b',
        'prefix' => 'AD',
        'extra'  => [
            'ad_platform'    => ['label' => 'Ad Platform',    'options' => ['LinkedIn Ads','Google Search','Google Display','Meta Ads','Twitter/X Ads','YouTube Ads','Programmatic','Other']],
            'ad_format'      => ['label' => 'Ad Format',      'options' => ['Single Image','Carousel','Video Ad','Lead Gen Form','Sponsored Content','Text Ad','Responsive Display','Other']],
            'ad_objective'   => ['label' => 'Ad Objective',   'options' => ['Brand Awareness','Lead Generation','Traffic','Engagement','Conversions','Retargeting','Other']],
            'headline1'      => ['label' => 'Headline (H1)'],
            'headline2'      => ['label' => 'Headline (H2)'],
            'body_copy'      => ['label' => 'Body Copy', 'textarea' => true],
            'cta_text'       => ['label' => 'CTA Text'],
            'dest_url'       => ['label' => 'Destination URL'],
            'audience_tgt'   => ['label' => 'Audience Targeting'],
            'budget'         => ['label' => 'Budget (₹)'],
            'run_start'      => ['label' => 'Run Start Date', 'date' => true],
            'run_end'        => ['label' => 'Run End Date',   'date' => true],
            'utm_params'     => ['label' => 'UTM Parameters'],
            'ad_account_id'  => ['label' => 'Ad Account / Campaign ID'],
            'linked_creative'=> ['label' => 'Linked Creative ID'],
        ]
    ],
    'seo' => [
        'label'  => 'SEO',
        'icon'   => 'fa-magnifying-glass',
        'color'  => '#16a085',
        'prefix' => 'SEO',
        'extra'  => [
            'seo_task_type'   => ['label' => 'SEO Task Type',   'options' => ['Keyword Research','On-Page Optimisation','Technical SEO','Content Optimisation','Link Building','Competitor Analysis','Schema Markup','Site Audit','Other']],
            'target_page_url' => ['label' => 'Target Page / URL'],
            'primary_kw'      => ['label' => 'Primary Keyword'],
            'secondary_kw'    => ['label' => 'Secondary Keywords'],
            'current_rank'    => ['label' => 'Current Ranking',  'number' => true],
            'target_rank'     => ['label' => 'Target Ranking',   'number' => true],
            'search_vol'      => ['label' => 'Monthly Search Vol','number' => true],
            'kw_difficulty'   => ['label' => 'Keyword Difficulty','number' => true],
            'on_page_changes' => ['label' => 'On-Page Changes', 'textarea' => true],
            'backlink_target' => ['label' => 'Backlink Target'],
            'tool_used'       => ['label' => 'Tool Used',        'options' => ['Ahrefs','SEMrush','Google Search Console','Moz','Screaming Frog','Ubersuggest','Other']],
            'tracking_impl'   => ['label' => 'Tracking Implemented?', 'options' => ['No','Yes – GA4','Yes – Search Console','Yes – Both']],
            'linked_content'  => ['label' => 'Linked Content ID'],
            'linked_lp'       => ['label' => 'Linked LP ID'],
        ]
    ],
    'webinar_event' => [
        'label'  => 'Webinars & Events',
        'icon'   => 'fa-microphone',
        'color'  => '#2c3e50',
        'prefix' => 'EV',
        'extra'  => [
            'event_type'       => ['label' => 'Event Type',    'options' => ['Webinar','Virtual Event','In-Person Event','Hybrid Event','Workshop','Roundtable','Conference','Podcast','Trade Show','Other']],
            'event_format'     => ['label' => 'Event Format',  'options' => ['Live','Pre-recorded','On-Demand','Hybrid']],
            'topic'            => ['label' => 'Topic / Agenda', 'textarea' => true],
            'speakers'         => ['label' => 'Speaker(s)',    'user' => true],
            'registration_url' => ['label' => 'Registration Link'],
            'platform'         => ['label' => 'Platform / Tool','options' => ['Zoom','Microsoft Teams','Google Meet','Hopin','Airmeet','LinkedIn Live','YouTube Live','Other']],
            'event_date'       => ['label' => 'Event Date',    'date' => true],
            'event_time'       => ['label' => 'Event Time'],
            'duration_mins'    => ['label' => 'Duration (mins)','number' => true],
            'expected_att'     => ['label' => 'Expected Attendees','number' => true],
            'actual_att'       => ['label' => 'Actual Attendees', 'number' => true],
            'recording_link'   => ['label' => 'Recording Link'],
            'followup_sent'    => ['label' => 'Follow-Up Email Sent?','options' => ['No','Yes – Sent','Scheduled']],
        ]
    ],
];

// Vertical codes for Campaign ID generation
$VERTICALS = [
    'DT'   => 'Digital Transformation',
    'IG'   => 'Industrial Goods & Consumer Products',
    'SE'   => 'Structural Engineering',
    'MT'   => 'MedTech',
    'EU'   => 'Energy & Utilities',
    'SUS'  => 'Sustainability',
    'BFSI' => 'BFSI',
    'PI'   => 'Product Innovation',
    'AI'   => 'SolidPro AI',
    'XX'   => 'Other / Cross-Vertical',
];

// Goal codes for Campaign ID generation
$GOAL_CODES = [
    'LG' => 'Lead Generation',
    'BD' => 'Brand / Demand Generation',
    'TL' => 'Thought Leadership',
    'EV' => 'Event / Webinar',
    'NR' => 'Nurture / Retention',
    'PL' => 'Product Launch',
    'AB' => 'ABM',
    'RE' => 'Re-engagement',
];

$CAMPAIGN_STATUSES = ['Planning', 'Active', 'Paused', 'Completed', 'Cancelled'];
$CAMPAIGN_TYPES    = ['Brand Awareness','Lead Generation','Demand Generation','Product Launch','Event Promotion','Thought Leadership','Nurture / Retention','ABM','Other'];
$GEOGRAPHIES       = ['India','United States','United Kingdom','UAE / Middle East','Europe','APAC','Global','Other'];

$ASSET_STATUSES = [
    'Briefed',
    'In Progress',
    'In Revision',
    'Approved by PH',
    'Approved by Manager',
    'Published / Live',
    'On Hold',
    'Cancelled',
];

$PRIORITIES     = ['Low', 'Medium', 'High', 'Critical'];
$APPROVAL_OPTS  = ['Pending', 'Approved', 'Changes Requested', 'Rejected'];
