# Security Incident Response Policy — Adxsway POS

**Last updated:** August 2026
**Maintained by:** Waleed Akhtar (Adxsway)

## Purpose
This policy outlines how Adxsway POS handles security incidents involving customer or merchant data accessed through the Shopify Admin API.

## Scope
Applies to all personal data processed by the app, including customer name, email, and order information retrieved from Shopify.

## Data Protection Measures
- Access to the app is restricted to authorized staff logins (username/password, bcrypt-hashed).
- All data is transmitted over HTTPS (encrypted in transit).
- Access to order/customer data is logged (see `storage/logs/data-access.log`) with user, timestamp, and IP address.
- Test (development store) and production (live store) data are kept in separate environments.

## Incident Detection
- Unusual access patterns are identified via the access log.
- Failed login attempts are monitored through the application's authentication system.

## Incident Response Steps
1. **Identify** — Confirm the nature and scope of the incident (e.g., unauthorized access, data leak).
2. **Contain** — Immediately revoke affected staff login credentials and reset passwords.
3. **Assess** — Review access logs to determine what data was affected and by whom.
4. **Notify** — Inform the store owner (merchant) within 72 hours of confirming a data breach involving customer personal data.
5. **Remediate** — Patch the vulnerability, rotate API credentials if compromised, and re-secure affected systems.
6. **Review** — Document the incident and update this policy to prevent recurrence.

## Data Retention
Order and customer data is retained only as long as necessary for order management and is deleted upon merchant request or app uninstallation.

## Contact
For security concerns, contact: Waleed Akhtar — Adxsway
