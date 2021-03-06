#!/usr/bin/env bash

echo "# Preparing GIT repos"

# Remove the git details from our repo so we can treat it as a path.
cd $TRAVIS_BUILD_DIR
rm .git -rf

# Create our main Drupal project.
echo "# Creating Drupal project"
composer create-project drupal-composer/drupal-project:8.x-dev $DRUPAL_BUILD_ROOT/drupal --stability dev --no-interaction --no-install
cd $DRUPAL_BUILD_ROOT/drupal

# Set our drupal core version.
composer require drupal/core $DRUPAL_CORE --no-update
composer require drupal/coder --no-update --dev

# Add our repositories for decoupled_auth, as well as re-adding
# the Drupal package repo.
echo "# Configuring package repos"
composer config repositories.0 path $TRAVIS_BUILD_DIR
composer config repositories.1 composer https://packages.drupal.org/8
composer config extra.enable-patching true

# Merge dev dependencies from decoupled_auth.
composer require wikimedia/composer-merge-plugin --no-update
php -r "\$data = json_decode(file_get_contents('composer.json'));\$data->extra->{'merge-plugin'}->require = ['"$TRAVIS_BUILD_DIR"/composer.json'];file_put_contents('composer.json', json_encode(\$data));"

# Now require decoupled_auth which will pull itself from the paths.
echo "# Requiring decoupled_auth"
composer require drupal/decoupled_auth dev-master
