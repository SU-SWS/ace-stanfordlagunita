#!/bin/bash

SECRET=$1

# Define paths array
PATHS=$(drush @supress.prod sqlq 'SELECT alias FROM path_alias a RIGHT JOIN node_field_data n ON a.path = CONCAT("/node/", n.nid) WHERE n.status = 1')

IFS=$'\n' read -rd '' -a PATHS_ARRAY <<<"$PATHS"


if [ -z "$2" ]; then
  DOW=$(date +%u)
else
  DOW=$2
fi

# Total number of paths
TOTAL_PATHS=${#PATHS_ARRAY[@]}

# Calculate the size of each piece
PIECE_SIZE=$((TOTAL_PATHS / 7))


# Calculate the start and end indices for the 4th piece
START_INDEX=$((PIECE_SIZE * ($DOW - 1)))

# Extract the piece for today
CHECK_PATHS=("${PATHS_ARRAY[@]:${START_INDEX}:${PIECE_SIZE}}")

# Iterate over each path
for CHECK_PATH in "${CHECK_PATHS[@]}"; do

  echo "https://www.sup.org${CHECK_PATH}"

  # Make a request.
  STATUS_CODE=$(curl -o /dev/null -s -w "%{http_code}" -I "https://www.sup.org${CHECK_PATH}")
  echo $STATUS_CODE

  if [[ "$STATUS_CODE" != "200" && "$STATUS_CODE" != "308" ]]; then

    echo "Failed to get ${CHECK_PATH}: ${STATUS_CODE}"
    # Invalidate the path.
    curl -s "https://www.sup.org/api/revalidate?secret=${SECRET}&path=${CHECK_PATH}"
  fi

done
exit 0
