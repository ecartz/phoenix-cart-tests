# CE install seeds info pages with pages_status=0 (draft). templates/default/includes/pages/info.php
# only loads rows with pages_status=1, so HTTP acceptance tests publish the footer slugs.
UPDATE pages SET pages_status = 1 WHERE slug IN ('privacy', 'conditions', 'shipping');
