## Template debugging (Bonsai trace comments)

Dev pages wrap every dynamically resolved template in id-matched HTML comments:

```html
<!-- bonsai:start id="4" tpl="_entry/default" type="entry" el="blogPost#64" section="blog" strategy="type" tried="_entry/blogPost/blog/my-post _entry/blogPost _entry/blog" -->
  <!-- bonsai:start id="1" tpl="_matrix/text" type="matrix" el="blogPost#64" block="text#123" --> … <!-- bonsai:end id="1" -->
<!-- bonsai:end id="4" -->
```

- **Fetch raw source** — browser accessibility/text tools strip HTML comments.
  Use `curl <url>`, `ddev exec curl <url>`, or `document.documentElement.outerHTML`.
  Trace tree only: `curl -s <url> | grep -oE '<!-- bonsai:(start|end)[^>]*-->'`
- **Attributes:** `tpl` = winning template path (relative to `templates/`);
  `type` = loader (entry/matrix/item/asset/category/product);
  `el` = owning element `{typeHandle}#{id}` (on matrix renders the owner — the
  block itself is `block="{blockType}#{id}"`); `section` = section handle;
  `strategy` = resolution order, present only when non-default;
  `tried` = more-specific paths that missed before the winner, omitted when the
  most specific path won.
- **Nesting:** a comment nested inside another is a child render; pairs match
  on `id` alone (`end` carries only the id).
- **DOM → source:** find the nearest enclosing `bonsai:start`, open its `tpl`,
  then follow plain `{% include %}`/`{% embed %}` statically from there.
- **Reading a fallthrough:** a generic winner (`…/default`) with a long `tried`
  list means a template you expected doesn't exist — the path that *should*
  have won is in `tried` (watch for section-first-shaped files in a type-first
  section, and vice versa). This catches "page renders fine but the wrong
  template is silently serving it" bugs.
- **Requires** `devMode` + the plugin's `llmMode` setting (or `BONSAI_LLM_MODE=true`).
