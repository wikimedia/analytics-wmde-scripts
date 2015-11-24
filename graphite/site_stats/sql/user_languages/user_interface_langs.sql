-- Get all user interface languages
-- When none set use the default which is 'en'
CREATE TEMPORARY TABLE staging.user_interface_langs AS (
	SELECT
		user.user_name as user,
		COALESCE(user_properties.up_value,"en") as language
	FROM wikidatawiki.user
	LEFT JOIN ( SELECT * FROM wikidatawiki.user_properties WHERE user_properties.up_property = 'language' ) AS user_properties
	ON user.user_id = user_properties.up_user
);