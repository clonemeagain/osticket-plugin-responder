# [osTicket](https://github.com/osTicket/) - Ticket Responder Plugin

Automatically responds to tickets when your office/helpdesk is closed.

Set your office-hours, outside of that, it will reply to new tickets as the user you specify.

## Caveats/Assumptions:

- Assumes [osTicket](https://github.com/osTicket/) v1.10+ is installed. The API changes a bit between versions.. Open an issue to support older versions.

## Install the plugin
- Download master [zip](https://github.com/clonemeagain/osticket-plugin-responder/archive/master.zip) and extract into `/include/plugins/responder`
- Install by selecting `Add New Plugin` from the Admin Panel => Manage => Plugins page, then select `Install` next to `Ticket Responder`.
- Enable the plugin by selecting the checkbox next to `Ticket Responder`, then select `More` and choose `Enable`, then select `Yes, Do it!`
- Configure by clicking `Ticket Responder` link name in the list of installed plugins.

## Configure the plugin

Visit the Admin-panel, select Manage => Plugins, choose the `Ticket Responder` plugin. 

- Specify a Canned Response to use as a Template for the response (therefore, can use same Variables)
- Specify an Agent username to send the response. (You can't just send one from nothing, needs a person)
- For each Day of the week, specify the hours the helpdesk is open for business, defaults to 0900-1700 M-F


### To reset the config
Simply "Delete" the plugin and isntall it again, all the configuration will reset from the defaults.
Admin panel -> Manage -> Plugins, slect the checkbox next to `Ticket Responder` then, from the drop-down select "Delete", then "Yes, Do it!" from the popup. It's not actually deleting the plugin, just it's config. 
Then go through the "Add New Plugin" process again.



Enjoy!
