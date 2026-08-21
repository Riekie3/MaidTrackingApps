<?php
// Every document/photo view goes through here — see stream_protected_file()
// in functions.php for why this is the real access control, not
// uploads/.htaccess. Unauthorized and not-found both return the same
// generic 404 so a guess can't be used to enumerate what exists.

require_once __DIR__ . '/includes/bootstrap.php';

$role = current_role();
if (!$role || !in_array($role, ['admin', 'agency', 'client', 'freelancer'], true)) {
    http_response_code(404);
    die('Not found.');
}

$kind = $_GET['kind'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$agencyId = current_id();
$freelancerId = current_id();

// Photos are the one file type meant to be seen by any signed-in role —
// clients need them on browse/candidate pages — but a client only ever
// gets the approved, public version of a housemaid, same gate as every
// other client-facing lookup (Housemaid::publicFindById).
if ($kind === 'housemaid_photo') {
    $housemaid = $role === 'client' ? Housemaid::publicFindById($id) : Housemaid::findById($id);
    $allowed = $housemaid && $housemaid['photo_path']
        && ($role === 'admin' || $role === 'client' || (int) $housemaid['agency_id'] === $agencyId);
    if (!$allowed) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_HOUSEMAID_DIR, '/') . '/' . $housemaid['photo_path']);
}

if ($kind === 'freelancer_photo') {
    $freelancer = $role === 'client' ? Freelancer::publicFindById($id) : Freelancer::findById($id);
    $allowed = $freelancer && $freelancer['photo_path']
        && ($role === 'admin' || $role === 'client' || ($role === 'freelancer' && (int) $freelancer['id'] === $freelancerId));
    if (!$allowed) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_FREELANCER_DIR, '/') . '/' . $freelancer['photo_path']);
}

if ($kind === 'freelancer_doc') {
    $doc = FreelancerDocument::find($id);
    $allowed = $doc && ($role === 'admin' || ($role === 'freelancer' && (int) $doc['freelancer_id'] === $freelancerId));
    if (!$allowed) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_FREELANCER_DIR, '/') . '/' . $doc['file_path']);
}

// A freelancer can view evidence for an incident reported against
// herself, same as an agency can for its own housemaid.
if ($kind === 'incident_evidence') {
    $incident = Incident::find($id);
    $allowed = $incident && $incident['evidence_path'] && (
        $role === 'admin'
        || ($incident['provider_type'] === 'housemaid' && (int) $incident['agency_id'] === $agencyId)
        || ($incident['provider_type'] === 'freelancer' && $role === 'freelancer' && (int) $incident['provider_id'] === $freelancerId)
    );
    if (!$allowed) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_INCIDENT_DIR, '/') . '/' . $incident['evidence_path']);
}

if ($role === 'client') {
    // Nothing else (documents, license, evidence) is ever client-facing.
    http_response_code(404);
    die('Not found.');
}

if ($role === 'freelancer') {
    // A freelancer's own login is scoped to her own photo/documents/
    // incident evidence only — agency license, other agencies'
    // housemaid documents, etc. stay unreachable.
    http_response_code(404);
    die('Not found.');
}

if ($kind === 'agency_license') {
    $agency = Agency::findById($id);
    $allowed = $agency && ($role === 'admin' || (int) $agency['id'] === $agencyId);
    if (!$allowed || !$agency['license_document_path']) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_AGENCY_DIR, '/') . '/' . $agency['license_document_path']);
}

if ($kind === 'housemaid_doc') {
    $doc = HousemaidDocument::find($id);
    $housemaid = $doc ? Housemaid::findById((int) $doc['housemaid_id']) : null;
    $allowed = $doc && $housemaid && ($role === 'admin' || (int) $housemaid['agency_id'] === $agencyId);
    if (!$allowed) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_HOUSEMAID_DIR, '/') . '/' . $doc['file_path']);
}

http_response_code(404);
die('Not found.');
