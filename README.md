# ChAoS MVC Module Builder 1.0.4



Admin-only; no public Module Builder view. Live source is user/modules/<slug>/.
Generated output is root /releases/<slug>/. Configure non-secret certification
identity in user/data/certified_developer.json and the query URL through
CHAOS_CERTIFICATION_ENDPOINT. Private keys are supplied for one signing request
and never stored. Uncertified users retain all development and unsigned build
capabilities.
