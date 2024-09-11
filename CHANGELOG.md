# Lagunita

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
