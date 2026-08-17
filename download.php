<?php
// Every document/photo view goes through here — see stream_protected_file()
// in functions.php for why this is the real access control, not
// uploads/.htaccess. Unauthorized and not-found both return the same
// generic 404 so a guess can't be used to enumerate what exists.

require_once __DIR__ . '/includes/bootstrap.php';

$role = current_role();
if (!$role || !in_array($role, ['admin', 'agency'], true)) {
    http_response_code(404);
    die('Not found.');
}

$kind = $_GET['kind'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$agencyId = current_id();

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

if ($kind === 'incident_evidence') {
    $incident = Incident::find($id);
    $housemaid = $incident ? Housemaid::findById((int) $incident['housemaid_id']) : null;
    $allowed = $incident && $incident['evidence_path'] && $housemaid
        && ($role === 'admin' || (int) $housemaid['agency_id'] === $agencyId);
    if (!$allowed) {
        http_response_code(404);
        die('Not found.');
    }
    stream_protected_file(rtrim(UPLOAD_INCIDENT_DIR, '/') . '/' . $incident['evidence_path']);
}

http_response_code(404);
die('Not found.');
