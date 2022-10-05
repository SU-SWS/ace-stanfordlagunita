
# General Site Architecture
This stack is designed to use a standard Drupal composer build process. This repository contains custom code/themes/etc
but is not intended to contain any contributed modules or drupal core itself. Those are downloaded and commited to a
remote artifact repository stored on Acquia hosting service. To learn more about the mechanism that performs that review the [tools documentation](tools.md)

The SUL site is split up in a standard "fully decoupled" approach. The backend is managed and maintained by Drupal. Site 
administrators will edit content and build out the website using the tools provided by drupal and contributed modules.
The frontend of the site is build with [NextJS](https://nextjs.org/). It uses OAuth to handle authentication with the Drupal
backend to fetch data. At this time, the decoupling is only 1 way: From Drupal to NextJS. There is no implementation or plan
to send data from the front end to Drupal for storage.

# Drupal Config Management
Each site has the ability to determine its own configuration management strategy. The default site in this repo will 
be using a configuration management that uses the configuration from the `sul_profile`. SUL profile is a git subtree that 
is branched off from [`stanford_profile`](https://github.com/SU-SWS/stanford_profile).
By default this is the behavior of all other sites unless defined within their own settings.php & blt.yml files. To pull updates from the `stanford_profile`, use the command `composer update-profile`. This will perform the necessary actions.
Though there may be merge conflict in the process and will require manual attention.

There are three options a site can choose from:
1. Do nothing and the configuration sync directory will use what is in `sul_profile`.
2. Modify the configuration sync directory to a desired directory such as another profile.
3. Modify the configuration sync directory to point to an empty directory. This
will bypass any configuration management strategy and the site's configuration will be updated via update hooks.


# Frontend Development
The frontend is build using [NextJS]() and is managed in the github repository [su-sws/sulgryphon-nextjs](https://github.com/SU-SWS/sulgryphon-nextjs). It's suggested to clone that repository into the root of this repository for easy development. See the documentation in that repository for more information.

# SSL Certificate
A wildcard certificate will be used with a 12 month expiration. This certificate will be updated
automatically via other tools.