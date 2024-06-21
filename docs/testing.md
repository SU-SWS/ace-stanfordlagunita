# Testing

## Github Actions
Github actions is configured to run 3 separate tests:
1. PHPUnit tests with coverage
2. Acceptance tests
   - These are codeception tests that do not require javascript. These are the fastest acceptance tests and should be
     primarily used if possible.
3. Functional tests
   - These are codeception tests that require javascript. These tests are slow and should be avoided if possible.
   - These tests run in a tunnel with browserstack.

## Local Testing


### PHPUnit
`blt phpunit` will run all phpunit tests. If you wish to run individual tests during development, first run
`tests:phpunit:config`. This will build a file `docroot/core/phpunit.xml` which is used for the unit testing.
Then you can run individual phpunit tests by `cd docroot && ../vendor/bin/phpunit -c core profiles/custom/sul_profile/...`.
To run with test coverage, add the following argument to the above `--coverage-html=../artifacts`.

### Acceptance
`blt codecption --suite=acceptance`. This shouldn't require anything additional to run. If you wish to run a specific
test, the recommendation is to add a group, which is defined in class/method's comment and will look like `@group content`.  Also you can specify which set of tests to run using the `--test` option. Use this to run only the tests within a specified profile. Then you can use that to run that specific test. Example: `blt codecption --suite=acceptance --group=lists --test=supress`.

### Functional
`blt codeception --suite=functional`. This relies on selenium standalone or some other chromedriver like mechanism.
The easiest way to get selenium is to use NPM. Follow [these instructions](https://www.npmjs.com/package/selenium-standalone).
With selenium running, open a new tab and execute the `blt` command. Similarly with the acceptance tests, it also
accepts `--group` options to run a single file/method.
