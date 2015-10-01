- - -
site stats bureaucrats
====================

The number of users in the bureaucrats on a give day.
Generated using the user_groups table.

SELECT ug_group, count(*) AS count FROM user_groups GROUP BY ug_group;