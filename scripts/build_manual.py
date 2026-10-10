"""Regenerate the packaged admin manual from USER_MANUAL.md (requires Markdown)."""

import hashlib
import re
from pathlib import Path

import markdown

root = Path(__file__).resolve().parent.parent
source = (root / "USER_MANUAL.md").read_text(encoding="utf-8")
content = re.sub(r"^(#{1,3}) ", r"\1# ", source, flags=re.MULTILINE)
content = content.replace("(INSTALL.md)", "(#install-and-open-the-add-on)")
content = content.replace("(TESTING.md)", "(#check-an-offer-before-enabling-it)")
html = markdown.markdown(content, extensions=["tables", "toc"])
html = html.replace("<table>", '<div class="co-manual-table"><table>').replace("</table>", "</table></div>")
target = root / "admin/includes/manuals/checkout_offer.html"
target.parent.mkdir(parents=True, exist_ok=True)
digest = hashlib.sha256(source.encode("utf-8")).hexdigest()
target.write_text("<!-- USER_MANUAL.md sha256: " + digest + " -->\n" + html + "\n", encoding="utf-8", newline="\n")
print("Admin manual generated: " + str(target))
