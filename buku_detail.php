<?php
declare(strict_types=1);

session_start();

if (!function_exists('jsonResponse')) {
    function jsonResponse(bool $ok, string $msg = '', mixed $data = null, int $httpCode = 200): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        $payload = ['ok' => $ok];
        if ($msg !== '')   $payload['msg']  = $msg;
        if ($data !== null) $payload['buku'] = $data;

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}

// ── Auth ─────────────────────────────────────────────────────────────────────
// Tamu (belum login) tetap boleh melihat detail buku — hanya lihat, tidak bisa
// suka/rating/simpan (itu ditangani terpisah oleh like_handler.php & rating_handler.php).
$is_guest = !isset($_SESSION['user_id']);

require_once 'db.php';

// $conn harus berupa mysqli (pastikan db.php mengembalikan instance mysqli)
assert($conn instanceof mysqli);

// ── Validasi input ────────────────────────────────────────────────────────────
$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$uid = $is_guest ? 0 : (int) $_SESSION['user_id'];

if ($id === false || $id === null) {
    jsonResponse(ok: false, msg: 'ID tidak valid', httpCode: 400);
}

if (!function_exists('fetchAll')) {
    function fetchAll(mysqli $db, string $sql, string $types, mixed ...$params): array
    {
        $stmt = $db->prepare($sql);
        if (!$stmt) throw new RuntimeException("Prepare gagal: {$db->error}");

        if ($types !== '') $stmt->bind_param($types, ...$params);

        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('fetchOne')) {
    function fetchOne(mysqli $db, string $sql, string $types, mixed ...$params): ?array
    {
        $rows = fetchAll($db, $sql, $types, ...$params);
        return $rows[0] ?? null;
    }
}

// ── Ambil data buku ───────────────────────────────────────────────────────────
$buku = fetchOne($conn, 'SELECT * FROM buku WHERE id = ? LIMIT 1', 'i', $id);

if ($buku === null) {
    jsonResponse(ok: false, msg: 'Buku tidak ditemukan', httpCode: 404);
}

// ── Likes ─────────────────────────────────────────────────────────────────────
$likeRow = fetchOne($conn,
    'SELECT COUNT(*) AS total FROM buku_likes WHERE buku_id = ?',
    'i', $id
);
$buku['jumlah_like'] = (int) ($likeRow['total'] ?? 0);

// ── Favorites ─────────────────────────────────────────────────────────────────
$favRow = fetchOne($conn,
    'SELECT COUNT(*) AS total FROM buku_favorites WHERE buku_id = ?',
    'i', $id
);
$buku['jumlah_favorit'] = (int) ($favRow['total'] ?? 0);

// ── Rating rata-rata ──────────────────────────────────────────────────────────
$ratRow = fetchOne($conn,
    'SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM buku_ratings WHERE buku_id = ?',
    'i', $id
);
$buku['rating_avg']   = ($ratRow['total'] ?? 0) > 0
    ? round((float) $ratRow['avg_r'], 1)
    : 0.0;
$buku['rating_total'] = (int) ($ratRow['total'] ?? 0);

// ── Rating user yang sedang login (tamu = 0, tidak ada rating) ────────────────
if ($is_guest) {
    $buku['user_rating'] = 0;
} else {
    $urRow = fetchOne($conn,
        'SELECT rating FROM buku_ratings WHERE user_id = ? AND buku_id = ? LIMIT 1',
        'ii', $uid, $id
    );
    $buku['user_rating'] = $urRow !== null ? (int) $urRow['rating'] : 0;
}

// ── Status favorit user (tamu = false) ────────────────────────────────────────
if ($is_guest) {
    $buku['user_favorit'] = false;
} else {
    $ufRow = fetchOne($conn,
        'SELECT id FROM buku_favorites WHERE user_id = ? AND buku_id = ? LIMIT 1',
        'ii', $uid, $id
    );
    $buku['user_favorit'] = $ufRow !== null;
}

// ── Status like user (tamu = false) ───────────────────────────────────────────
if ($is_guest) {
    $buku['user_like'] = false;
} else {
    $ulRow = fetchOne($conn,
        'SELECT id FROM buku_likes WHERE user_id = ? AND buku_id = ? LIMIT 1',
        'ii', $uid, $id
    );
    $buku['user_like'] = $ulRow !== null;
}

// ── Kirim respons ─────────────────────────────────────────────────────────────
jsonResponse(ok: true, data: $buku);