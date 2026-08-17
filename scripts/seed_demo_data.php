<?php
// Populates a rich, fictional demo dataset — deliberately run through the
// real models (Agency::create, Housemaid::create, etc.) rather than raw
// SQL, because passport/national-ID numbers are AES-256-GCM encrypted at
// write time using this environment's local config/secrets.php key. A
// static SQL dump of ciphertext would only decrypt correctly on the
// machine it was generated on; running it through the app itself means
// this script works identically on any fresh install, whatever key it
// generated for itself.
//
// Every name below is a fictional placeholder for demo purposes, not a
// real person. Photos are synthetic initials-avatars, not photos of real
// people — deliberately chosen over stock/AI face photos so no actual
// likeness ends up attached to a fabricated profile that includes
// invented incident reports. The avatar files live in the versioned
// database/demo_assets/avatars/ (uploads/ itself is gitignored, so
// they'd be missing on a fresh clone otherwise) and get copied into
// place below. Run once against a freshly migrated database:
//   php scripts/seed_demo_data.php

require_once __DIR__ . '/../includes/bootstrap.php';

$avatarSrcDir = __DIR__ . '/../database/demo_assets/avatars';
if (!is_dir(UPLOAD_HOUSEMAID_DIR)) {
    mkdir(UPLOAD_HOUSEMAID_DIR, 0755, true);
}
foreach (glob($avatarSrcDir . '/*.png') as $avatar) {
    copy($avatar, UPLOAD_HOUSEMAID_DIR . '/' . basename($avatar));
}
echo 'Copied ' . count(glob($avatarSrcDir . '/*.png')) . " demo avatars into uploads/housemaids/\n";

function skill_id(string $name): int
{
    static $cache = [];
    if (!$cache) {
        foreach (MasterData::skills() as $s) $cache[$s['name']] = (int) $s['id'];
    }
    return $cache[$name] ?? 0;
}
function lang_id(string $name): int
{
    static $cache = [];
    if (!$cache) {
        foreach (MasterData::languages() as $l) $cache[$l['name']] = (int) $l['id'];
    }
    return $cache[$name] ?? 0;
}
function country_id(string $name): int
{
    static $cache = [];
    if (!$cache) {
        foreach (MasterData::countries() as $c) $cache[$c['name']] = (int) $c['id'];
    }
    return $cache[$name] ?? 0;
}

$agencyPass = password_hash('AgencyDemo123', PASSWORD_DEFAULT);
$clientPass = password_hash('ClientDemo123', PASSWORD_DEFAULT);

echo "Seeding agencies...\n";

$agencies = [
    'sinar_jaya' => Agency::create([
        'company_name' => 'Sinar Jaya Maid Agency', 'registration_number' => 'SSM-2019-00123',
        'license_document_path' => null, 'contact_person' => 'Aina Rahman', 'email' => 'agency1@sinarjaya.test',
        'phone' => '+60123456789', 'password_hash' => $agencyPass,
        'address' => '12 Jalan Ampang, 50450 Kuala Lumpur', 'bio' => 'Serving Klang Valley families since 2019, specialising in childcare and elderly care placements.',
    ]),
    'harmoni' => Agency::create([
        'company_name' => 'Harmoni Domestic Services', 'registration_number' => 'SSM-2021-00456',
        'license_document_path' => null, 'contact_person' => 'Farid Hassan', 'email' => 'agency2@test.local',
        'phone' => '+60134567890', 'password_hash' => $agencyPass,
        'address' => '45 Jalan SS2/24, 47300 Petaling Jaya, Selangor', 'bio' => 'A boutique agency focused on long-term, live-in placements across the Klang Valley.',
    ]),
    'kasih_sayang' => Agency::create([
        'company_name' => 'Kasih Sayang Agency', 'registration_number' => 'SSM-2017-00789',
        'license_document_path' => null, 'contact_person' => 'Mei Ling Tan', 'email' => 'agency3@test.local',
        'phone' => '+60145678901', 'password_hash' => $agencyPass,
        'address' => '8 Jalan Molek, 81100 Johor Bahru, Johor', 'bio' => 'Southern Malaysia\'s longest-running housemaid placement agency, established 2017.',
    ]),
    'ceria' => Agency::create([
        'company_name' => 'Ceria Home Care', 'registration_number' => 'SSM-2026-01011',
        'license_document_path' => null, 'contact_person' => 'Nabila Yusof', 'email' => 'agency4@test.local',
        'phone' => '+60156789012', 'password_hash' => $agencyPass,
        'address' => '3 Jalan Burma, 10350 George Town, Penang', 'bio' => 'Newly registered — awaiting Admin approval.',
    ]),
];
// Approve all but Ceria Home Care, which stays Pending to populate the
// Admin approval queue in the live demo.
$adminId = 1;
foreach (['sinar_jaya', 'harmoni', 'kasih_sayang'] as $key) {
    Agency::approve($agencies[$key], $adminId);
}
echo "  4 agencies (3 approved, 1 pending)\n";

echo "Seeding housemaids...\n";

function make_housemaid(int $agencyId, array $core, array $skillNames, array $langNames, ?array $decision = null): int
{
    global $adminId;
    $core['agency_id'] = $agencyId;
    $id = Housemaid::create($core);
    Housemaid::attachSkills($id, array_map('skill_id', $skillNames));
    Housemaid::attachLanguages($id, array_map('lang_id', $langNames));
    if ($decision === ['approve']) {
        Housemaid::approve($id, $adminId);
    } elseif ($decision && $decision[0] === 'reject') {
        Housemaid::reject($id, $adminId, $decision[1]);
    }
    return $id;
}

$hm = [];

$hm['siti'] = make_housemaid($agencies['sinar_jaya'], [
    'full_name' => 'Siti Marlina', 'photo_path' => 'demo_siti.png', 'date_of_birth' => '1992-03-14', 'gender' => 'female',
    'nationality_country_id' => country_id('Indonesia'), 'marital_status' => 'single', 'religion' => 'Islam',
    'passport_number' => 'A1234567', 'passport_expiry' => '2029-06-30', 'work_permit_number' => 'WP-998877',
    'work_permit_expiry' => '2027-01-15', 'national_id_number' => 'ID-556677',
    'home_address' => "Jl. Merdeka No. 5, Surabaya, Indonesia", 'emergency_contact_name' => 'Budi Marlina',
    'emergency_contact_phone' => '+6281234567890', 'current_staying_address' => 'Sinar Jaya Agency Dorm, Kuala Lumpur',
    'years_experience' => 5,
], ['Childcare - Infant', 'Childcare - School-age', 'Cooking - Local Malaysian'], ['Bahasa Indonesia', 'Bahasa Malaysia'], ['approve']);

$hm['maria'] = make_housemaid($agencies['sinar_jaya'], [
    'full_name' => 'Maria Santos', 'photo_path' => 'demo_maria.png', 'date_of_birth' => '1988-11-02', 'gender' => 'female',
    'nationality_country_id' => country_id('Philippines'), 'marital_status' => 'married', 'religion' => 'Catholic',
    'passport_number' => 'P7654321', 'passport_expiry' => '2028-02-20', 'work_permit_number' => 'WP-334455',
    'work_permit_expiry' => '2026-10-05', 'national_id_number' => 'PH-112233',
    'home_address' => 'Barangay San Isidro, Cebu City, Philippines', 'emergency_contact_name' => 'Jose Santos',
    'emergency_contact_phone' => '+639171234567', 'current_staying_address' => "Employer's residence, Petaling Jaya",
    'years_experience' => 8,
], ['Elderly Care', 'Patient / Nursing Care', 'Cooking - Western'], ['Tagalog', 'English'], ['approve']);
Housemaid::setAvailability($hm['maria'], 'placed');

$hm['aye'] = make_housemaid($agencies['sinar_jaya'], [
    'full_name' => 'Aye Aye Win', 'photo_path' => 'demo_aye.png', 'date_of_birth' => '1995-07-19', 'gender' => 'female',
    'nationality_country_id' => country_id('Myanmar'), 'marital_status' => 'single', 'religion' => 'Buddhist',
    'passport_number' => 'MM445566', 'passport_expiry' => '2027-09-12', 'work_permit_number' => 'WP-556611',
    'work_permit_expiry' => '2026-12-01', 'national_id_number' => 'MM-778899',
    'home_address' => 'Yangon, Myanmar', 'emergency_contact_name' => 'Thant Zin',
    'emergency_contact_phone' => '+959123456789', 'current_staying_address' => 'Sinar Jaya Agency Dorm, Kuala Lumpur',
    'years_experience' => 3,
], ['General Housekeeping', 'Ironing', 'Laundry'], ['Burmese'], ['approve']);

$hm['dewi'] = make_housemaid($agencies['harmoni'], [
    'full_name' => 'Dewi Anggraini', 'photo_path' => 'demo_dewi.png', 'date_of_birth' => '1990-05-25', 'gender' => 'female',
    'nationality_country_id' => country_id('Indonesia'), 'marital_status' => 'divorced', 'religion' => 'Islam',
    'passport_number' => 'A9988776', 'passport_expiry' => '2028-11-30', 'work_permit_number' => 'WP-221199',
    'work_permit_expiry' => '2027-04-18', 'national_id_number' => 'ID-334422',
    'home_address' => 'Bandung, Jawa Barat, Indonesia', 'emergency_contact_name' => 'Siti Anggraini',
    'emergency_contact_phone' => '+6281298765432', 'current_staying_address' => 'Harmoni Agency Dorm, Petaling Jaya',
    'years_experience' => 6,
], ['Deep Cleaning', 'Cooking - Local Malaysian', 'Pet Care'], ['Bahasa Indonesia', 'Bahasa Malaysia'], ['approve']);

$hm['grace'] = make_housemaid($agencies['harmoni'], [
    'full_name' => 'Grace Fernandez', 'photo_path' => 'demo_grace.png', 'date_of_birth' => '1993-01-08', 'gender' => 'female',
    'nationality_country_id' => country_id('Philippines'), 'marital_status' => 'single', 'religion' => 'Catholic',
    'passport_number' => 'P1122334', 'passport_expiry' => '2029-03-15', 'work_permit_number' => null,
    'work_permit_expiry' => null, 'national_id_number' => 'PH-556677',
    'home_address' => 'Davao City, Philippines', 'emergency_contact_name' => 'Anna Fernandez',
    'emergency_contact_phone' => '+639189876543', 'current_staying_address' => 'Harmoni Agency Dorm, Petaling Jaya',
    'years_experience' => 2,
], ['Childcare - Infant', 'Cooking - Western'], ['Tagalog', 'English']); // left Pending on purpose

$hm['thida'] = make_housemaid($agencies['kasih_sayang'], [
    'full_name' => 'Thida Oo', 'photo_path' => 'demo_thida.png', 'date_of_birth' => '1991-09-30', 'gender' => 'female',
    'nationality_country_id' => country_id('Myanmar'), 'marital_status' => 'married', 'religion' => 'Buddhist',
    'passport_number' => 'MM667788', 'passport_expiry' => '2028-07-22', 'work_permit_number' => 'WP-887744',
    'work_permit_expiry' => '2027-02-10', 'national_id_number' => 'MM-990011',
    'home_address' => 'Mandalay, Myanmar', 'emergency_contact_name' => 'Kyaw Kyaw',
    'emergency_contact_phone' => '+959187654321', 'current_staying_address' => 'Kasih Sayang Agency Dorm, Johor Bahru',
    'years_experience' => 4,
], ['Elderly Care', 'General Housekeeping'], ['Burmese', 'Bahasa Malaysia'], ['approve']);

$hm['nimade'] = make_housemaid($agencies['kasih_sayang'], [
    'full_name' => 'Ni Made Sari', 'photo_path' => 'demo_nimade.png', 'date_of_birth' => '1989-12-11', 'gender' => 'female',
    'nationality_country_id' => country_id('Indonesia'), 'marital_status' => 'single', 'religion' => 'Hindu',
    'passport_number' => 'A5544332', 'passport_expiry' => '2027-05-08', 'work_permit_number' => 'WP-443322',
    'work_permit_expiry' => '2026-11-25', 'national_id_number' => 'ID-667788',
    'home_address' => 'Denpasar, Bali, Indonesia', 'emergency_contact_name' => 'Wayan Sari',
    'emergency_contact_phone' => '+6281387654321', 'current_staying_address' => "Employer's residence, Johor Bahru",
    'years_experience' => 7,
], ['Cooking - Indian', 'Cooking - Local Malaysian', 'Driving'], ['Bahasa Indonesia'], ['approve']);
Housemaid::setAvailability($hm['nimade'], 'on_leave');

$hm['josephine'] = make_housemaid($agencies['kasih_sayang'], [
    'full_name' => 'Josephine Cruz', 'photo_path' => 'demo_josephine.png', 'date_of_birth' => '1994-04-17', 'gender' => 'female',
    'nationality_country_id' => country_id('Philippines'), 'marital_status' => 'single', 'religion' => 'Catholic',
    'passport_number' => 'P9988112', 'passport_expiry' => '2028-01-19', 'work_permit_number' => null,
    'work_permit_expiry' => null, 'national_id_number' => 'PH-223344',
    'home_address' => 'Iloilo City, Philippines', 'emergency_contact_name' => 'Rosa Cruz',
    'emergency_contact_phone' => '+639201234567', 'current_staying_address' => 'Kasih Sayang Agency Dorm, Johor Bahru',
    'years_experience' => 1,
], ['General Housekeeping'], ['Tagalog'], ['reject', 'Incomplete medical checkup document — please resubmit with a valid report.']);

echo "  8 housemaids across 3 agencies (6 approved in varied availability states, 1 pending, 1 rejected)\n";

echo "Seeding clients...\n";

$clients = [
    'nurul' => Client::create(['name' => 'Nurul Aisyah', 'email' => 'client1@test.local', 'phone' => '+60129998888', 'password_hash' => $clientPass, 'address' => 'Petaling Jaya, Selangor']),
    'weiling' => Client::create(['name' => 'Tan Wei Ling', 'email' => 'client2@test.local', 'phone' => '+60138887777', 'password_hash' => $clientPass, 'address' => 'Subang Jaya, Selangor']),
    'rajesh' => Client::create(['name' => 'Rajesh Kumar', 'email' => 'client3@test.local', 'phone' => '+60147776666', 'password_hash' => $clientPass, 'address' => 'Bangsar, Kuala Lumpur']),
    'michelle' => Client::create(['name' => 'Michelle Wong', 'email' => 'client4@test.local', 'phone' => '+60156665555', 'password_hash' => $clientPass, 'address' => 'Mont Kiara, Kuala Lumpur']),
];
foreach ($clients as $id) {
    Client::markVerified($id);
}
echo "  4 clients, all verified\n";

echo "Seeding bookings + reviews...\n";

// 1. Completed, reviewed — the "hero" profile with full history.
$b1 = Booking::create(['client_id' => $clients['nurul'], 'housemaid_id' => $hm['siti'], 'agency_id' => $agencies['sinar_jaya'], 'start_date' => '2026-01-15', 'end_date' => '2026-07-15', 'notes' => 'Household of 4, two young children, need help with elderly care too.']);
Booking::accept($b1);
Booking::complete($b1);
Review::create(['booking_id' => $b1, 'client_id' => $clients['nurul'], 'housemaid_id' => $hm['siti'], 'rating_reliability' => 5, 'rating_skill' => 4, 'rating_hygiene' => 5, 'rating_communication' => 5, 'comment' => 'Excellent with the kids, very punctual and tidy. Highly recommended.']);
Review::addAgencyResponse((int) getDB()->query("SELECT id FROM reviews WHERE booking_id = $b1")->fetchColumn(), $agencies['sinar_jaya'], "Thank you for the kind words, Nurul! We're proud of Siti's work.");

// 2. Accepted / active placement.
$b2 = Booking::create(['client_id' => $clients['weiling'], 'housemaid_id' => $hm['maria'], 'agency_id' => $agencies['sinar_jaya'], 'start_date' => '2026-06-01', 'end_date' => null, 'notes' => 'Live-in, caring for my elderly mother.']);
Booking::accept($b2);

// 3. Requested — sitting in the agency's pending queue.
$b3 = Booking::create(['client_id' => $clients['rajesh'], 'housemaid_id' => $hm['dewi'], 'agency_id' => $agencies['harmoni'], 'start_date' => '2026-09-01', 'end_date' => null, 'notes' => 'Household of 3, need general housekeeping and cooking.']);

// 4. Completed, reviewed — second review for variety.
$b4 = Booking::create(['client_id' => $clients['michelle'], 'housemaid_id' => $hm['thida'], 'agency_id' => $agencies['kasih_sayang'], 'start_date' => '2025-10-01', 'end_date' => '2026-04-01', 'notes' => 'Caring for my grandmother, live-out arrangement.']);
Booking::accept($b4);
Booking::complete($b4);
Review::create(['booking_id' => $b4, 'client_id' => $clients['michelle'], 'housemaid_id' => $hm['thida'], 'rating_reliability' => 4, 'rating_skill' => 5, 'rating_hygiene' => 4, 'rating_communication' => 3, 'comment' => 'Very gentle and patient with my grandmother. Communication could be a bit clearer but overall a great experience.']);

// 5. Declined.
$b5 = Booking::create(['client_id' => $clients['nurul'], 'housemaid_id' => $hm['aye'], 'agency_id' => $agencies['sinar_jaya'], 'start_date' => '2026-08-20', 'end_date' => null, 'notes' => 'Short-term cover needed for 2 months.']);
Booking::decline($b5);

// 6. Cancelled after acceptance.
$b6 = Booking::create(['client_id' => $clients['weiling'], 'housemaid_id' => $hm['nimade'], 'agency_id' => $agencies['kasih_sayang'], 'start_date' => '2026-05-01', 'end_date' => null, 'notes' => 'Cooking-focused role.']);
Booking::accept($b6);
Booking::cancel($b6, 'client', $clients['weiling']);
Housemaid::setAvailability($hm['nimade'], 'on_leave'); // restore the on-leave state set above, since cancel() reverts to available

echo "  6 bookings (2 completed+reviewed, 1 accepted, 1 requested, 1 declined, 1 cancelled)\n";

echo "Seeding incidents (all four workflow states)...\n";

Incident::create(['housemaid_id' => $hm['maria'], 'reported_by_type' => 'agency', 'reported_by_id' => $agencies['sinar_jaya'], 'incident_type' => 'other', 'description' => 'Client raised a minor punctuality concern during week 3 — flagging for visibility, not yet reviewed.', 'evidence_path' => null]);

$incUnderReview = Incident::create(['housemaid_id' => $hm['dewi'], 'reported_by_type' => 'client', 'reported_by_id' => $clients['rajesh'], 'incident_type' => 'contract_breach', 'description' => 'Alleges agreed duties were not followed during a trial day. Admin is looking into it.', 'evidence_path' => null]);
Incident::markUnderReview($incUnderReview, $adminId);

$incVerified = Incident::create(['housemaid_id' => $hm['siti'], 'reported_by_type' => 'agency', 'reported_by_id' => $agencies['sinar_jaya'], 'incident_type' => 'contract_breach', 'description' => 'Left a prior placement 2 weeks early without notice.', 'evidence_path' => null]);
Incident::verify($incVerified, $adminId);

$incDismissed = Incident::create(['housemaid_id' => $hm['siti'], 'reported_by_type' => 'agency', 'reported_by_id' => $agencies['sinar_jaya'], 'incident_type' => 'other', 'description' => 'Unverified complaint, could not be substantiated.', 'evidence_path' => null]);
Incident::dismiss($incDismissed, $adminId);

echo "  4 incidents — 1 reported, 1 under review, 1 verified, 1 dismissed\n";

echo "\nDone. Demo dataset ready.\n";
