---
paths:
  - 'app/**'
---

# App

## Product events go through ProductEventRecorder only
Every product event (roadmap evidence: submissions, verification outcomes, kit asset copies, share intents) must be written through App\Services\ProductEventRecorder — never insert into product_events directly and never accept free-form event names or properties from the client. Client-recordable names are whitelisted on App\Enums\ProductEventName::canBeRecordedByClient(); lifecycle events are recorded server-side only. The daily scheduled Cloud recheck deliberately records nothing.
