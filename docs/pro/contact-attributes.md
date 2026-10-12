# Contact attributes <span class="pro-badge">Pro</span>

<ProNotice />

With the Contacts module on, the Contacts tab of **Brevo > Configuration** lists every contact data
(`first_name`, `total_spent`…) with the Brevo attribute it goes to in that channel.

![Contact data and their Brevo attributes](/images/pro-contact-attributes.png){.screenshot}

- Leave it empty to keep the name from `odiseo_sylius_brevo.contacts.attributes`.
- Type another one: the account's attributes are suggested.
- Tick **Don't send** to leave it out.

Only the attributes the account has are sent. Create the missing ones with:

```bash
bin/console odiseo:brevo:attributes:setup --channel=WEB
```

Channels sharing a Brevo account share its contacts: each sync uses the names of the channel it comes
from, so keep their mappings alike.

Attributes added with the `odiseo_brevo.contact_attribute_provider` tag show up too.
