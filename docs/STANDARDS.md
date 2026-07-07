\# AgencyOS Engineering \& UI Standards



\## Version



Current Version: 1.0



\---



\# General Rules



Every module must follow the same architecture.



Model

Migration

Filament Resource

Validation

Relationships

Permissions

Documentation



No shortcuts.



\---



\# Form Layout Standard



Every Filament form must use Sections.



Order:



1\. Basic Information

2\. Business Information

3\. Contact Information

4\. Documents

5\. Settings

6\. Audit (read only)



Never mix unrelated fields inside one section.



\---



\# Table Standard



Every table must include:



✔ Search



✔ Sort



✔ Filters



✔ Bulk Actions



✔ Toggle Columns



✔ Status Badge



✔ Created Date



✔ Updated Date



\---



\# Database Standard



Use foreign keys.



Never store repeated text.



Example:



department\_id



NOT



department



\---



\# Relationships



belongsTo



hasMany



belongsToMany



Always use Eloquent relationships.



Never manually join tables.



\---



\# Uploads



Always use FileUpload.



Store inside:



storage/app/public



Organize folders by module.



Example:



employees/



customers/



projects/



\---



\# Status



Every master module should include:



is\_active



SoftDeletes



created\_by



updated\_by



created\_at



updated\_at



\---



\# Documentation



Every completed sprint requires:



Git Commit



Git Push



Database Documentation Update (if schema changed)



CHANGELOG Update (for major architectural changes)



\---



\# Coding Style



Never duplicate code.



Prefer Services over Controllers for business logic.



Keep Resources clean.



Business logic belongs inside Services.



\---



\# Goal



Every AgencyOS module should look and behave consistently.

