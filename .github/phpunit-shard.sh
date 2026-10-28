#!/usr/bin/env bash
# Run one shard of the Unit and Kernel PHPUnit tests with PHP coverage output.
#
# Usage: .github/phpunit-shard.sh <shard index (0 based)> <total shards>
#
# Test files are sorted with Kernel tests first, then dealt out round-robin so
# the slower Kernel tests are spread evenly across shards.

set -euo pipefail

SHARD=$1
TOTAL=$2

cd "$(dirname "$0")/../docroot"

FILES=$(find -L ../docroot/modules/custom ../docroot/profiles/custom -path '*/tests/src/*' -name '*Test.php' \
  | grep -E '/tests/src/(Unit|Kernel)/' \
  | awk '{ print (index($0, "/tests/src/Kernel/") ? 0 : 1) " " $0 }' \
  | sort \
  | cut -d' ' -f2- \
  | awk -v shard="$SHARD" -v total="$TOTAL" '(NR - 1) % total == shard')

if [ -z "$FILES" ]; then
  echo "No tests assigned to shard $SHARD."
  exit 0
fi

echo "Shard $SHARD of $TOTAL running $(echo "$FILES" | wc -l) test files:"
echo "$FILES"

mkdir -p ../artifacts/phpunit/coverage

# shellcheck disable=SC2086
../vendor/bin/phpunit --configuration=core --filter="/(Unit|Kernel)/" \
  --log-junit="../artifacts/phpunit/results-$SHARD.xml" \
  --coverage-php="../artifacts/phpunit/coverage/shard-$SHARD.cov" \
  $FILES
