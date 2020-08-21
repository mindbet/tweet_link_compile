Tweet Link Compile
======


This module imports tweets through Twitter API for Drupal 8.

Tweets are imported by Cron or through an admin menu link.

Collected tweets are then parsed for outbound links, and those links are compiled by popularity.

To keep the listing fresh, outbound links are no longer counted after a time period you set. Default is 30 hours.

The tweet retrieval code in this module is based on Drupal module Get Tweets [https://www.drupal.org/project/get_tweets]

This module can be seen in action at http://trending.health [http://trending.health]

You have the option to save the collected tweets as Drupal nodes.


Registration with Twitter:
-------------
Before you start the installation process you must register on
Twitter and create your own application.
You will get "Consumer Key", "Consumer Secret".

Requirements:
-------------
PHP versions listed as "active support" or "security fixes only"
are supported.

Installation:
-------------
1. Download the module;
2. Enable it;
3. Go to /admin/config/services/tweet_link_compile and set your credentials;
4. Add twitter users to follow.
5. Use the "Retrieve new tweets" tab to pull in a new tweets, or run cron.
6. Your tweets are now imported into nodes
7. As the tweets are imported, they are parsed for outbound links.
7. A block titled "Most popular links" is created that can be placed where needed.
