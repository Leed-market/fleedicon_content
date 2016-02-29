# Fleedicon_content
## Description
This [Leed](https://github.com/ldleman/Leed)'s plugin store and display your feeds' favicons.

## FAQ
### Where the favicons are stored?
Favicons stored inside the `favicons` folder.

### How can I add favicons to my Leed's theme?
#### Inside the side bar
/!\ `menu_pre_feed_link` hook needed.
`{if="isset($value->favicon)"}{$value->favicon}{/if}`

#### Inside the main view
/!\ `event_pre_title` hook needed.
`{if="isset($value2['favicon'])"}{$value2['favicon']}{/if}`

### Where can I find the the logs?
* Log files are located inside the 'logs' folder
* The `check` file contains the last date we search for favicons
* The `no-favicon` file contains feeds URLs without favicon

## Ideas
* Handle new favicons (with a monthly cron?)
* Fetch favicons with an AJAX call on install (with a hook?)
* Add a field to configure checking recurrence for new (updated?) favicons (currently 1 month)
