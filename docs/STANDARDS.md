\# AgencyOS Development Standards



Version: v0.1.0



\---



\# Core Principles



AgencyOS is built as a modular CRM + ERP platform.



Every module must follow the same architecture.



No shortcuts.



No duplicated business logic.



No editing vendor files.



\---



\# Development Order



Every module must be built in this order.



1\. Migration

2\. Model

3\. Relationships

4\. Seeder (if required)

5\. Filament Resource

6\. Business Logic

7\. Testing

8\. Git Commit

9\. Git Push



\---



\# Folder Structure



app/



Actions/



Services/



Helpers/



Observers/



Policies/



Events/



Listeners/



Models/



Notifications/



Filament/



\---



\# Business Logic



Business logic must NEVER be written inside:



Controllers



Filament Resources



Blade Views



Business logic belongs inside:



Services



Actions



Observers



\---



\# Database Rules



Every operational table should contain:



created\_by



updated\_by



timestamps



Soft Deletes (only where required)



\---



\# Permission Naming



Module.Action



Examples



employees.view



employees.create



employees.edit



employees.delete



leads.assign



payments.verify



invoices.share



\---



\# Filament Resource Standard



Navigation



Navigation Group



Navigation Sort



Navigation Icon



Record Title



Every resource must define these.



\---



\# Form Standard



Large forms must use Tabs.



General



Business



Documents



Communication



Settings



Notes



Employee module:



General



Employment



Address



Emergency Contact



Documents



Account



Notes



\---



\# Table Standard



Every table should include:



Search



Filters



Sorting



Bulk Actions



Status Badge



Created Date



Updated Date



Export (later)



\---



\# Naming



Models



Singular



Employee



Customer



Invoice



Database



Plural



employees



customers



invoices



Resources



EmployeeResource



CustomerResource



InvoiceResource



\---



\# Git Workflow



Feature Branch



↓



Commit



↓



Push



↓



Merge into main



Never commit unfinished code.



\---



\# UI Standard



Use consistent spacing.



Use section headings.



Use icons.



Avoid long vertical forms.



Prefer Tabs over collapsible sections.



\---



\# Security



Use Spatie Roles \& Permissions.



Never hardcode permissions.



Never check role names directly.



Always use permissions.



\---



\# Audit



Every important action should be logged.



Future modules will use Activity Log.



\---



\# Future Modules



Company Profile



Employees



Services



Packages



Add-ons



Leads



Customers



Quotations



Payments



Invoices



Receipts



Projects



Tasks



Documents



Reports



Renewals



Settings



Dashboard



\---



AgencyOS Engineering Standard



Locked

