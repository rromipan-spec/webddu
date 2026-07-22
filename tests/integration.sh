#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${TEST_BASE_URL:-http://127.0.0.1:8080}"
WORK_DIR="$(mktemp -d)"
COOKIE_JAR="$WORK_DIR/cookies.txt"
RESPONSE="$WORK_DIR/response.json"
TEST_SUFFIX="$(date +%s)-$RANDOM"
ARTICLE_SLUG="artikel-integration-$TEST_SUFFIX"
SCHEDULED_SLUG="artikel-terjadwal-$TEST_SUFFIX"
PROGRAM_SLUG="program-integration-$TEST_SUFFIX"

cleanup() {
  rm -rf "$WORK_DIR"
}
trap cleanup EXIT

fail() {
  echo "[TEST GAGAL] $1" >&2
  if [[ -f "$RESPONSE" ]]; then
    echo "Respons terakhir:" >&2
    cat "$RESPONSE" >&2
    echo >&2
  fi
  exit 1
}

request() {
  local expected="$1"
  shift
  local status
  status="$(curl --silent --show-error --output "$RESPONSE" --write-out '%{http_code}' "$@")"
  [[ "$status" == "$expected" ]] || fail "HTTP $status, seharusnya $expected: $*"
}

assert_json() {
  jq -e "$1" "$RESPONSE" >/dev/null || fail "JSON tidak memenuhi: $1"
}

echo '[1/12] Health check'
request 200 "$BASE_URL/health.php"
assert_json '.ok == true and .status == "healthy"'

echo '[2/12] Sesi anonim dan login salah'
request 200 -c "$COOKIE_JAR" "$BASE_URL/api/index.php?resource=session"
assert_json '.ok == true and .authenticated == false'
request 401 -c "$COOKIE_JAR" -H 'Content-Type: application/json' \
  --data '{"email":"integration-test@ddu.invalid","password":"password-salah"}' \
  "$BASE_URL/api/index.php?resource=login"

echo '[3/12] Login admin dan CSRF'
request 200 -c "$COOKIE_JAR" -b "$COOKIE_JAR" -H 'Content-Type: application/json' \
  --data '{"email":"integration-test@ddu.invalid","password":"DduIntegrationTest!2026"}' \
  "$BASE_URL/api/index.php?resource=login"
assert_json '.ok == true and .role == "super_admin" and (.csrf | length) >= 32'
CSRF="$(jq -r '.csrf' "$RESPONSE")"
request 419 -b "$COOKIE_JAR" -H 'Content-Type: application/json' \
  --data "{\"title\":\"Ditolak CSRF\",\"slug\":\"ditolak-csrf-$TEST_SUFFIX\",\"status\":\"draft\"}" \
  "$BASE_URL/api/index.php?resource=posts"

echo '[4/12] Buat artikel draft'
ARTICLE_PAYLOAD="$(jq -nc \
  --arg title 'Artikel Integration Test' --arg slug "$ARTICLE_SLUG" \
  '{title:$title,slug:$slug,excerpt:"Ringkasan pengujian",content:"<p>Konten pengujian aman.</p>",category:"Pengujian",status:"draft",gallery_images:[],hero_images:[]}')"
request 201 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" -H 'Content-Type: application/json' \
  --data "$ARTICLE_PAYLOAD" "$BASE_URL/api/index.php?resource=posts"
assert_json '.ok == true and (.id | tonumber) > 0'
ARTICLE_ID="$(jq -r '.id' "$RESPONSE")"
request 404 "$BASE_URL/api/index.php?resource=posts&slug=$ARTICLE_SLUG"
request 200 -b "$COOKIE_JAR" "$BASE_URL/api/index.php?resource=posts&slug=$ARTICLE_SLUG&preview=1"
assert_json '.data.status == "draft"'

echo '[5/12] Publikasikan artikel dan periksa sitemap'
ARTICLE_PUBLISHED="$(jq --argjson id "$ARTICLE_ID" '. + {id:$id,status:"published",published_at:""}' <<< "$ARTICLE_PAYLOAD")"
request 200 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" -H 'Content-Type: application/json' \
  --data "$ARTICLE_PUBLISHED" "$BASE_URL/api/index.php?resource=posts"
request 200 "$BASE_URL/api/index.php?resource=posts&slug=$ARTICLE_SLUG"
assert_json '.data.status == "published"'
request 200 "$BASE_URL/sitemap.php"
grep -q "/artikel/$ARTICLE_SLUG" "$RESPONSE" || fail 'Artikel publik belum masuk sitemap.'

echo '[6/12] Jadwal publikasi masa depan'
SCHEDULED_PAYLOAD="$(jq -nc --arg slug "$SCHEDULED_SLUG" \
  '{title:"Artikel Terjadwal Integration",slug:$slug,excerpt:"Terjadwal",content:"<p>Belum boleh tampil.</p>",category:"Pengujian",status:"published",published_at:"2099-01-01T00:00",gallery_images:[],hero_images:[]}')"
request 201 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" -H 'Content-Type: application/json' \
  --data "$SCHEDULED_PAYLOAD" "$BASE_URL/api/index.php?resource=posts"
SCHEDULED_ID="$(jq -r '.id' "$RESPONSE")"
request 404 "$BASE_URL/api/index.php?resource=posts&slug=$SCHEDULED_SLUG"

echo '[7/12] CRUD program'
PROGRAM_PAYLOAD="$(jq -nc --arg slug "$PROGRAM_SLUG" \
  '{title:"Program Integration Test",slug:$slug,excerpt:"Ringkasan program",content:"<p>Program pengujian.</p>",category:"Pengujian",status:"published",published_at:"",featured_order:1,gallery_images:[]}')"
request 201 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" -H 'Content-Type: application/json' \
  --data "$PROGRAM_PAYLOAD" "$BASE_URL/api/index.php?resource=programs"
PROGRAM_ID="$(jq -r '.id' "$RESPONSE")"
PROGRAM_UPDATED="$(jq --argjson id "$PROGRAM_ID" '. + {id:$id,title:"Program Integration Diperbarui",featured_order:2}' <<< "$PROGRAM_PAYLOAD")"
request 200 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" -H 'Content-Type: application/json' \
  --data "$PROGRAM_UPDATED" "$BASE_URL/api/index.php?resource=programs"
request 200 "$BASE_URL/api/index.php?resource=programs&slug=$PROGRAM_SLUG"
assert_json '.data.title == "Program Integration Diperbarui" and .data.featured_order == 2 and .data.status == "published"'

echo '[8/12] Upload gambar valid'
php -r '$image=imagecreatetruecolor(32,32); $blue=imagecolorallocate($image,20,80,160); imagefill($image,0,0,$blue); imagepng($image,$argv[1]); imagedestroy($image);' "$WORK_DIR/test.png"
request 201 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" \
  -F "image=@$WORK_DIR/test.png;type=image/png" "$BASE_URL/api/index.php?resource=upload"
assert_json '.ok == true and (.url | startswith("/uploads/"))'

echo '[9/12] Tolak upload berbahaya'
printf '%s' '<?php echo "tidak boleh"; ?>' > "$WORK_DIR/malicious.php"
request 422 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" \
  -F "image=@$WORK_DIR/malicious.php;type=image/png" "$BASE_URL/api/index.php?resource=upload"

echo '[10/12] Statistik dan riwayat perubahan'
request 201 -b "$COOKIE_JAR" -H 'Content-Type: application/json' \
  --data '{"type":"wa_click"}' "$BASE_URL/api/index.php?resource=stats"
request 200 -b "$COOKIE_JAR" "$BASE_URL/api/index.php?resource=stats"
assert_json '.data.wa_click >= 1'
request 200 -b "$COOKIE_JAR" "$BASE_URL/api/index.php?resource=history&limit=100"
assert_json '[.data[].summary] | any(contains("Artikel Integration Test"))'

echo '[11/12] Hapus data pengujian'
request 200 -X DELETE -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" \
  "$BASE_URL/api/index.php?resource=posts&id=$ARTICLE_ID"
request 200 -X DELETE -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" \
  "$BASE_URL/api/index.php?resource=posts&id=$SCHEDULED_ID"
request 200 -X DELETE -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" \
  "$BASE_URL/api/index.php?resource=programs&id=$PROGRAM_ID"
request 404 "$BASE_URL/api/index.php?resource=posts&slug=$ARTICLE_SLUG"

echo '[12/12] Logout dan proteksi admin'
request 200 -b "$COOKIE_JAR" -H "X-CSRF-Token: $CSRF" -X POST \
  "$BASE_URL/api/index.php?resource=logout"
request 401 -b "$COOKIE_JAR" "$BASE_URL/api/index.php?resource=history"
request 404 "$BASE_URL/halaman-tidak-ditemukan"

echo 'Semua integration test berhasil.'
