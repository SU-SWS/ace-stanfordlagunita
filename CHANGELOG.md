# Lagunita

3.0.2
--------------------------------------------------------------------------------
_Release Date: 2025-10-01_

- Sum: Adjusted config split settings for search api algolia search
- SUM: fixup search api
- SUM: Fix grading field migration import bundle
- STVP26-74 STVP26-75: Add "Body" field to news and basic pages (#993)
- SUL23-818 | Update font awesome icon help text (#994)
- Exposed body field in graphql

3.0.1
--------------------------------------------------------------------------------
_Release Date: 2025-09-19_

- SUM-335: Adjust algolia indexing to include links (#216)
- SUL23-710 | adjust news and branch location metadata (#215)
- SUP: Add permission to run importer from the UI (#213)

3.0.0
--------------------------------------------------------------------------------
_Release Date: 2025-09-09_

- SUL23-818 | add icon option field to library alert (#209)
- Create anesthesia profile (#210)
- SUL23-295: Added fields for branch metadata (#208)
- SUL: Re-enable news topics edge graphql
- SUL23-835 | Update events view and add sul button background variant (#207)
- Updated graphql configs for all sites
- Added default local drush config
- SUL23-834 | add experience field to sul localist importer (#204)
- SUL23-815 | enable stat card graphql fragments and add layout options config (#202)
- SUL23-394 | update news banner help text (#201)
- SUP: added drush command to unpublish orphan books
- Fixed post-db-copy command
- Updated Drupal 11

2.9.0
--------------------------------------------------------------------------------
_Release Date: 2025-08-07_

- Replace BLT tools with Drush tools.


2.8.10
--------------------------------------------------------------------------------
_Release Date: 2025-08-01_

- SUP: Add alternative price data
- SUM: Updated testimonial banner type field option
- Updated dependencies.


2.8.9
--------------------------------------------------------------------------------
_Release Date: 2025-07-16_

- Updated dependencies.


2.8.8
--------------------------------------------------------------------------------
_Release Date: 2025-07-01_

- SUP: Fix ebook format logic in case multiple records exist from the API

2.8.7
--------------------------------------------------------------------------------
_Release Date: 2025-06-30_

- SUP: Remove access to opportunity content type

2.8.6
--------------------------------------------------------------------------------
_Release Date: 2025-06-26_

- SUP: Add digital sale and discount fields for book prices
- Inheritance from stanford_profile
  - D8CORE-6470: Update Event Importer help text
  - D8CORE-8047: Manage Basic Pages content management view
  - D8CORE-7836: Improved Events content management
  - D8CORE-7736: Add unpublished site banner to all pages
  - D8CORE-8042 - External source field for person
  - D8CORE-7843 D8CORE-8086 Add stat card and section background color options
  - D8CORE 8000
  - Require headline in stat card
  - D8CORE-8133: Opportunities Default Sort
  - D8CORE-7987: Copy the utility links and buttons for the mobile display
  - D8CORE-8045: Adding spacing to header bottom when there is no navigation menu.
  - Added heading level selection for stat card paragraph
  - D8CORE-8036: RSS Feed For News
  - Adjust opportunity filters to use radio buttons
  - D8CORE-8071 Added an "Imported" field populated
  - D8CORE-8063 Fix html structure on opportunity list items
  - D8CORE-8019: Move opportunity fields into a view for better styles

2.8.5
--------------------------------------------------------------------------------
_Release Date: 2025-06-05_

- HOTFIX: updated views_taxonomy_term_name_depth to 7.3.0
- added patch tag

2.8.3
--------------------------------------------------------------------------------
_Release Date: 2025-06-04_

- SUM: Updated course form setting
- SUL23-789 | add boolean for rosette in horizontal card behaviors (#183)
- SUL23-769 | update SUL ckeditor html styles and add location link style (#182)
- SUM-328: Add grading taxonomy and field to summer courses (#184)
- SUL23-792 | update ckeditor configs for link options (#185)

2.8.2
--------------------------------------------------------------------------------
_Release Date: 2025-05-07_

- SUP: Connect book subjects during import
- SUP: Error handle with API query for ebook data
- SUL23-768: BE update for card behaviors and fields
- SUP: Add sorting view option for books
- SUP: add author last name as separate field for view sorting
- SUM-316: Add learner type taxonomy and list display
- SUM: Removed unused column in CSV Importer

2.8.1
--------------------------------------------------------------------------------
_Release Date: 2025-04-08_

- SUM-322 SUM-321: Editing styles to "at a glance" and the "action link" in a card (#174)
- SUP: Allow unlimited related books
- SUM: Styles for editing experience. (#168)
- SUP: Import epub and pdf data for ebooks (#171)

2.8.0
--------------------------------------------------------------------------------
_Release Date: 2025-03-12_

- SUL23-723: added Additional Info field to places to study
- SUL23-721: Added sticky at the top of lists option to places to study
- SUL23-722: Allow selection of location hour on study place content
- SUM: Add classes to paragraph types to support authoring styles

2.7.1
--------------------------------------------------------------------------------
_Release Date: 2025-02-10_

- SUM-307: Add export ability for media content for course importer (#161)

2.7.0
--------------------------------------------------------------------------------
_Release Date: 2025-01-23_

- Fixed default directory config sync path.

2.6.4
--------------------------------------------------------------------------------
_Release Date: 2025-01-23_

- SUM: Use html instead of markdown for course importer
- Change global settings to early settings (#158)
- SUL23-701 SUL-703: Home page image banner & Location hours paragraph types (#156)
- SUM: Add instructors to algolia index
- SUM: Add class number for csv import
- SUM: Fixed CSV importer

2.6.1
--------------------------------------------------------------------------------
_Release Date: 2024-12-04_

- Updated github workflows
- Updated stanford_profile_helper to dev
- Updated SUL algolia configuration
- updated metatags configs for SUP
- updated book and award configs for SUP
- updated supress_helper modules
- updated and removed patch for `drupal/filefield_paths`

2.6.0
--------------------------------------------------------------------------------
_Release Date: 2024-11-26_

- Updated inheritance, including accordion paragraph type.

2.5.6
--------------------------------------------------------------------------------
_Release Date: 2024-11-05_

- SUP: Send published year separate from published date.

2.5.5
--------------------------------------------------------------------------------
_Release Date: 2024-11-05_

- Fixed Github actions: Dont deploy a tag if no tag was created
- SUP: Adjust book and award importers

2.5.4
--------------------------------------------------------------------------------
_Release Date: 2024-10-31_

- Updated dependencies
- require node_revision_delete development version to fix some issues.

2.5.2
--------------------------------------------------------------------------------
_Release Date: 2024-10-11_

- SUP: Include isbn 13 data in algolia
- SUL23-596: Provide ascending and descending event display options (#143)
- SUL23-637: Added sidenav layout option to news content type
- SUL23-643: Added help text to layout selection fields
- SUP: Fixed award importer
- SUL-637: Improve basic page node form
- SUL-636 SUL-637: Add "On This Page" layout option with related links fields
- SUP: Skip award import if year is empty

2.5.1
--------------------------------------------------------------------------------
_Release Date: 2024-10-02_

- SUP: Add "New Releases" and "Top Sellers" book displays

2.5.0
--------------------------------------------------------------------------------
_Release Date: 2024-10-01_

- SUL: Fix person importer
- SUL-621 SUL-635: Add sort to table views and reverted event importer config
- SUP: allow epub file extension uploads
- fixup update hook
- SUP, SUM: Limit images to 2MB
- SUP: Update view arguments helper text
- SUP: Added seasonal list view display
- SUL23-485: Allow span tag and lang attribute in minimal html wysiwyg
- SUP: Uncomment cover image queuer
- SUP: Added weight to excerpt pages for sort orders
- SUL23-480 Expose image credit field on media
- remove access content permissions for anonymous
- Added nextjs preview domains for summer and press dev and test sites
- SUM: Added edit domain support
- SUP: Import TOC as excerpt page

2.4.8
--------------------------------------------------------------------------------
_Release Date: 2024-09-11_
- Drupal 10.3.3
- SUL: tabular layout frontend support

2.4.7
--------------------------------------------------------------------------------
_Release Date: 2024-08-09_
- SUM: removed banner from home page install content by @pookmish in #131
- SUM-196 - Hiding message type and label by @mdyoung3 in #133
- Created Config Split for library for easier inheritance by @pookmish in #134


2.4.6
--------------------------------------------------------------------------------
_Release Date: 2024-07-10_
- Summer: Adjust top banner form display setting to match UI of paragraphs fields
- SUM - card number and banner overlay fixup (#120)
- SUP: Excerpt ancillary pages content type and permission updates (#121)
- SUL23-489: Modified places to study view to allow table option (#122)
- Skip decoupled user sanitizing
- SUM & PRESS: Remove anonymous access to site
- Update stanford profile helper
- SUL23-490: Added branch locations view to support tabular layout (#125)
- SUM-186 Moving transparent background to card from pill. (#124)
- Update drupal 10.3
- Updated dependencies
- patch environment indicator
- SUP: Use isbn to indicate digital projects
- SUM-137 - Adjust permissions for taxonomy and content type (#116)
- SUP & SUM: Added some wysiwyg styles
- SUP: Import book tags
- SUL23-504: Relabeled view display to match the others (#129)
- SUM: Update pill banner form display settings

2.4.5
--------------------------------------------------------------------------------
_Release Date: 2024-06-12_

- SUP: Adjusted protected files access logic
- SUP: Headline not required in list paragraph
- sup: Added imprint taxonomy filter for book view
- SUP: Add imprints and series to algolia index data
- SUP: Added additional contextual argument for book view
- Fixed incorrectly named update hooks for press and summer'
- SUM: Adjustments to make editing page better
- Sum: Fixed testimonial banner behaviors
- SUM-111 - Testimonial banner paragraph (#111)

2.4.4
--------------------------------------------------------------------------------
_Release Date: 2024-06-05_

- Built awards entity type with an importer and image importing funtion
- Added summer and press artifact repos and drush aliases
- Fixed press book cover url
- Added and configured stage file proxy
- SUP-63: Press Book content type (#73)
- Book list view and disable unused content types (#85)
- SUM-123 - Adding new Summer Course Content Type. (#83)
- SUP New paragraph types stubbed out (#86)
- Set imported node to update if an award or image is created
- SUP-104 Enable readonly algolia on non-prod environments
- Fixed Supress Event subscriber
- SUP-194 Added banner carousel paragraph and fields (#88)
- SUM-175 Add button links to site settings config
- Add "Book Type" selection for digital projects
- Updated algolia search to use subtitle as the summary
- SUP-198 SUP-181 SUP-174 Blog teaser, Search Form, and Carousel paragraphs (#89)
- Press: Disable image banner paragraph type (#90)
- Press: Add card style behavior to allow taxonomy card links. (#91)
- Press: added intl_card field
- SUM-174 Create component for favorites page
- SUM-162 - Summer Course CSV Importer (#87)
- SUM-172 Build calculator component (#93)
- SUL-23-447: Added people table view (#92)
- SUP-233 Press added book excerpt field
- Press added Award entity access handler
- Press added file list label field
- Press: added list paragraph eyebrow. Summer: added courses pattern
- SUM-113 - Pill Card variant (#94)
- SUM-117 Enable accordian on basic pages (#95)
- Press: Added background image field for slideshow
- Press: add award winners
- Press: Updated book cards for indexing
- Press added ebook retailer data aggregating
- SUM-114 - Adding video paragraph type (#97)
- SUM-113 - addendum: adding pill background variant (#100)
- Summer: Enable GraphQL for Video Paragraph (#101)
- SUL23-434: Set up shared tags events view (#98)
- SUM-108 Top Banner Paragraph
- SUM-113 - background state condition depending on pill selection (#102)
- Summer: added accordions paragraph for grouping multiple accordions
- Press: Update book import with new subject structure
- Press: added display option for file list paragraph
- Press: Prevent unnecessary migration updates
- Summer: updated paragraph displays
- Updated sws modules & dependencies
- SUM-109 - Arc Paragraph component
- SUM-116 Carousel slideshow component
- SUM-110 - adding banner component behavior. (#110)
- SUM-186 - Transparent background card style (#112)
- SUM-115 - At-a-glance Paragraph (#108)
- Enabled graphql compose fragments module for local dev
- Summer remove top banner headline field in favor of page title
- SUL: Fixed contextual filter and sorting for shared tag event view
- Press: added protected file upload media type
- SUP: Updated user roles for taxonomies
- SUM-112 - Pill banner paragraph component (#114)
- SUP: Disable imprints, subjects and series data import


2.4.0
--------------------------------------------------------------------------------
_Release Date: 2024-03-20_

- SUM-126 Establish summer profile
- SUL23-417 - Place of Study cards (room number, name, and image) (#72)
- Update lando configuration for local development (#67)
- Fixed SUL libguide lookup process plugin


2.3.0
--------------------------------------------------------------------------------
_Release Date: 2024-02-09_

- Updated profiles and dependencies
- Provision Press site.

2.2.0
--------------------------------------------------------------------------------
_Release Date: 2024-01-12_

- Modify the event importer to update events less often

2.2.0
--------------------------------------------------------------------------------
_Release Date: 2023_

- Graphql support
