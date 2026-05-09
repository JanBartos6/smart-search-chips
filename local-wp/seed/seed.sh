#!/bin/sh
set -eu

WP_URL="${WP_URL:-http://localhost:8899}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASS="${WP_ADMIN_PASS:-password}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.test}"
SEED_MARKER="_sschp_local_seed"

echo "Waiting for WordPress files and database..."
tries=0
until wp core version >/dev/null 2>&1 && wp db check >/dev/null 2>&1; do
  tries=$((tries + 1))
  if [ "$tries" -gt 60 ]; then
    echo "WordPress did not become ready in time." >&2
    exit 1
  fi
  sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install \
    --url="$WP_URL" \
    --title="TrailForge Supply" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASS" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email
fi

wp theme activate trailforge
wp plugin activate smart-search-chips || true

wp rewrite structure '/%postname%/'
wp option update blogdescription 'A fake outdoor gear catalog for Smart Search Chips testing.'
wp option update timezone_string 'Europe/Budapest'

wp eval '
update_option( "sschp_post_types", array( "post" ) );
update_option( "sschp_max_chips", 10 );
update_option( "sschp_max_results", 12 );
update_option( "sschp_search_results_count", 50 );
update_option( "sschp_chip_color", "#245f46" );
update_option( "sschp_chip_text_color", "#ffffff" );
update_option( "sschp_chip_inactive_color", "#fffaf1" );
update_option( "sschp_chip_inactive_text_color", "#21352d" );
'

seeded_ids="$(wp post list --post_type=any --meta_key="$SEED_MARKER" --format=ids)"
if [ -n "$seeded_ids" ]; then
  wp post delete $seeded_ids --force >/dev/null
fi

for default_slug in hello-world sample-page; do
  default_ids="$(wp post list --post_type=any --name="$default_slug" --format=ids)"
  if [ -n "$default_ids" ]; then
    wp post delete $default_ids --force >/dev/null
  fi
done

create_page() {
  title="$1"
  slug="$2"
  content="$3"
  id="$(wp post create --post_type=page --post_title="$title" --post_name="$slug" --post_content="$content" --post_status=publish --porcelain)"
  wp post meta update "$id" "$SEED_MARKER" 1 >/dev/null
  echo "$id"
}

home_id="$(create_page "TrailForge Supply" "home" "Local Smart Search Chips test site.")"
search_id="$(create_page "Search Gear" "search-gear" "[sschp_search placeholder=\"Try tent, stove, waterproof, coffee, ultralight...\"]")"

wp option update show_on_front page >/dev/null
wp option update page_on_front "$home_id" >/dev/null

create_post() {
  title="$1"
  slug="$2"
  content="$3"
  chips="$4"
  category="$5"

  term_id="$(wp term list category --name="$category" --field=term_id --format=ids | head -n 1 || true)"
  if [ -z "$term_id" ]; then
    term_id="$(wp term create category "$category" --porcelain)"
  fi

  id="$(wp post create --post_type=post --post_title="$title" --post_name="$slug" --post_content="$content" --post_status=publish --porcelain)"
  wp post term set "$id" category "$term_id" >/dev/null
  wp post meta update "$id" sschp_tags "$chips" >/dev/null
  wp post meta update "$id" "$SEED_MARKER" 1 >/dev/null
}

create_post "RidgeLite Two-Person Tent" "ridgelite-two-person-tent" "A freestanding backpacking tent for wet alpine weekends. Search for tent, waterproof, rainfly, or ultralight to test chip grouping." "tent; backpacking; waterproof; ultralight; rainfly; two person; alpine; Weight 1300 g; Capacity 2 person; Packed Length 46 cm" "Shelters"
create_post "Pinebreak Family Tent" "pinebreak-family-tent" "A roomy car camping tent with standing height, storm vents, and a durable floor for family trips." "tent; family camping; waterproof; car camping; standing height; storm vents; Weight 5200 g; Capacity 4 person; Packed Length 68 cm" "Shelters"
create_post "StormPeak Bivy" "stormpeak-bivy" "Minimal emergency shelter for fast solo missions where pack size matters more than comfort." "bivy; solo; emergency shelter; ultralight; waterproof; compact; Weight 420 g; Capacity 1 person; Packed Length 28 cm" "Shelters"
create_post "EmberJet Trail Stove" "emberjet-trail-stove" "Compact canister stove for boiling water fast on backpacking routes and overnight hikes." "stove; cooking; backpacking; compact; canister fuel; fast boil; Weight 89 g; Power 3200 W; Boil Time 3.2 min" "Camp Kitchen"
create_post "IronPan Camp Skillet" "ironpan-camp-skillet" "A heavy-duty skillet for fire cooking, camp breakfasts, and basecamp meals." "skillet; cooking; cast iron; camp kitchen; basecamp; fire cooking; Weight 1450 g; Diameter 26 cm" "Camp Kitchen"
create_post "MossCup Pour-Over Kit" "mosscup-pour-over-kit" "A collapsible coffee kit for slow mornings outside the tent." "coffee; pour over; camp kitchen; collapsible; lightweight; morning ritual; Weight 180 g; Capacity 0.6 L" "Camp Kitchen"
create_post "CloudLoft Sleeping Bag 20F" "cloudloft-sleeping-bag-20f" "Synthetic sleeping bag for cold shoulder-season camping and damp conditions." "sleeping bag; 20f; synthetic; cold weather; insulated; damp conditions; Weight 980 g; Comfort 20 F; Packed Length 34 cm" "Sleep Systems"
create_post "DriftPad Sleeping Mat" "driftpad-sleeping-mat" "Inflatable sleeping pad with quiet fabric and a high R-value for colder nights." "sleeping pad; insulated; inflatable; sleep; cold weather; high r value; Weight 610 g; R Value 4.8; Packed Length 25 cm" "Sleep Systems"
create_post "SummitShell Rain Jacket" "summitshell-rain-jacket" "Breathable waterproof shell for exposed hikes, trail storms, and daily carry." "rain jacket; waterproof; breathable; hiking; shell; packable; Weight 310 g; Waterproof Rating 20000 mm" "Apparel"
create_post "CedarTrail Hiking Pants" "cedartrail-hiking-pants" "Stretch hiking pants with reinforced knees and quick-dry fabric for rough routes." "hiking pants; quick dry; stretch; reinforced; trail; durable; Weight 390 g; Inseam 81 cm" "Apparel"
create_post "Northline 45L Backpack" "northline-45l-backpack" "A balanced 45 liter pack for weekend backpacking, tent storage, and food carries." "backpack; 45l; backpacking; weekend; load carry; hydration compatible; Weight 1280 g; Capacity 45 L" "Packs"
create_post "Riverbend Dry Bag Set" "riverbend-dry-bag-set" "Color-coded waterproof dry bags for paddling, rainy hikes, and organizing pack contents." "dry bag; waterproof; paddling; organization; rain; pack storage; Weight 240 g; Capacity 20 L" "Packs"

wp cache flush >/dev/null

echo ""
echo "Local WordPress test site is ready."
echo "Site:  $WP_URL"
echo "Admin: $WP_URL/wp-admin"
echo "Login: $WP_ADMIN_USER / $WP_ADMIN_PASS"
echo "Search test page ID: $search_id"
