# TODO: Implement Quotation Status (Sent, Accepted, Rejected)

## Steps to Complete

- [x] Add status dropdown to the quote form in Module8/quotes.php (options: Draft, Sent, Accepted, Rejected; default to Draft for new quotes)
- [x] Update JavaScript in Module8/quotes.php to set status value in edit modal (openEditQuoteModal)
- [x] Update JavaScript in Module8/quotes.php to set default status in create modal (openCreateQuoteModal)
- [x] Update JavaScript in Module8/quotes.php to include status in save FormData
- [x] Modify Module8/api/quotes.php 'save' action to accept and save status (default to 'draft' if not provided)
- [ ] Test creating a new quote (status defaults to Draft)
- [ ] Test editing a quote to change status to Sent, Accepted, Rejected
- [ ] Verify status displays correctly in the list
