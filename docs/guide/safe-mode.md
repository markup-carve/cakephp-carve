# Safe rendering in Carve 0.2

The secure default has `rawHtml: false` and a URL policy that denies `javascript`, `vbscript`, `data`, and `file` schemes. Explicit raw HTML is escaped. A denied URL renders without its destination.

For visitor content, use the `comment` preset or set `profile: comment`. For a trusted author, set `rawHtml: true` or use the `trusted` preset. The URL policy still applies. You can set `urlPolicy` with a `deny` list or an `allow` list using the Carve API's option shape.

The 0.1 `safeMode` and `onDisallowed` options are gone. Update configuration to `rawHtml`, `urlPolicy`, and `profile`. Unknown 0.1 option names raise an error so a security setting is not silently ignored.
