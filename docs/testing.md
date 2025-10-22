# Testing

## Github Actions
Github actions is configured to run 3 separate tests:
1. PHPUnit tests with coverage
2. Acceptance tests
   - These are codeception tests that do not require javascript. These are the fastest acceptance tests and should be
     primarily used if possible.
3. Functional tests
   - These are codeception tests that require javascript. These tests are slow and should be used less often.

Codeception provides the ability to run tests in "shards". This allows tests to
be run in parallel, speeding up the overall test time. The number of shards is
defined in a "Repository Variable". See that [value here](https://github.com/SU-SWS/ace-stanfordlagunita/settings/variables/actions).
If the variable doesn't exist, the Github action will default to 3 shards. The
number of shards is used per test type and per site. So each site will run (2 * n)
number of github action tasks.

## Local Testing


### PHPUnit
To scaffold the PHPUnit configuration file (`docroot/core/phpunit.xml`), run:
`drush sws:source:tests:phpunit:config`

To run all PHPUnit tests, use:
`drush sws:source:tests:phpunit`

To run individual tests during development, ensure `docroot/core/phpunit.xml` exists (see above). Then run:
`cd docroot && ../vendor/bin/phpunit -c core profiles/custom/sul_profile/...`

To run with test coverage, add:
`--with-coverage` to the `drush sws:source:tests:phpunit` command, or use PHPUnit's `--coverage-html=../artifacts` option.

### Acceptance
To run acceptance tests, use:
`drush codeception --suite=acceptance`

To run a specific test or group, use the `--group` and `--test` options. For example:
`drush codeception --suite=acceptance --group=lists --test=supress`

### Functional
To run functional tests, use:
`drush codeception --suite=functional`

These tests rely on selenium standalone or a chromedriver. The easiest way to get selenium is to use NPM. Follow [these instructions](https://www.npmjs.com/package/selenium-standalone).

With selenium running, open a new tab and execute the `drush codeception` command. As with acceptance tests, you can use the `--group` option to run a single file/method.
