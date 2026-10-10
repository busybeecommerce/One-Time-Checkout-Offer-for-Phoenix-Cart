"""Regenerate the packaged admin guide from USER_MANUAL.md (requires Markdown)."""

import hashlib
import html
import re
from pathlib import Path

import markdown
from markdown.extensions.toc import slugify


def render(content: str) -> str:
    content = re.sub(r"^(#{1,3}) ", r"\1# ", content, flags=re.MULTILINE)
    content = content.replace("(INSTALL.md)", "(#install-and-open-the-add-on)")
    content = content.replace("(TESTING.md)", "(#check-an-offer-before-enabling-it)")
    result = markdown.markdown(content, extensions=["tables", "toc"])
    return result.replace("<table>", '<div class="co-manual-table" tabindex="0" role="region" aria-label="Reference table"><table>').replace("</table>", "</table></div>")


def build(source: str) -> str:
    parts = re.split(r"^## (.+)\n", source, flags=re.MULTILINE)
    sections = dict(zip(parts[1::2], parts[2::2]))
    if len(sections) != len(parts[1::2]):
        raise ValueError("Manual section headings must be unique")
    groups = [
        ("Get started", ["Install and open the add-on", "Create your first offer", "Use the administration tabs"]),
        ("Offers & pricing", ["Set basket-value tiers", "Add products and offer prices", "Understand the customer checkout journey"]),
        ("Design & wording", ["Choose inline or modal display", "Choose a template", "Customise appearance and alignment", "Change the wording"]),
        ("Check & maintain", ["Check an offer before enabling it", "Troubleshooting", "Disable or remove the add-on"]),
    ]
    titles = [title for _, tasks in groups for title in tasks]
    if set(sections) != set(titles + ["Contents", "Quick start"]):
        raise ValueError("Manual sections do not match the grouped guides")
    introduction = parts[0].strip().split("\n\n", 1)
    if len(introduction) != 2:
        raise ValueError("Manual introduction is incomplete")
    output = ['<header class="co-manual-header"><div><span class="co-manual-kicker">Checkout Offers · Help centre</span>' + render(introduction[0]) + '<p>Set up, style and maintain your checkout offers.</p></div>']
    output.append('<div class="co-manual-search" hidden><label for="co-manual-query">Find help</label><input id="co-manual-query" type="search" placeholder="Search pricing, stock, alignment…" aria-controls="co-manual-topics"><p role="status" aria-live="polite"></p></div></header>')
    output.append('<div class="co-manual-tools"><button type="button" class="co-manual-home" hidden>Quick start</button><a href="#co-manual-topics">Browse topics ↓</a></div>')
    output.append('<div class="co-manual-grid"><section class="co-manual-reader" id="co-manual-reader" tabindex="-1" role="region" aria-label="Getting started"><div class="co-manual-welcome">')
    output.append('<span class="co-manual-kicker">Your first offer in three steps</span>' + render("## Quick start\n" + sections["Quick start"]))
    output.append('<details class="co-manual-about"><summary>About this add-on and guide</summary>' + render(introduction[1]) + '</details></div></section>')
    output.append('<div id="co-manual-topics" tabindex="-1" class="co-manual-topics" aria-label="Guide topics">')
    for label, tasks in groups:
        output.append('<section class="co-manual-group"><h3>' + html.escape(label) + '</h3>')
        for title in tasks:
            anchor = slugify(title, "-")
            safe_title = html.escape(title)
            output.append(f'<details class="co-manual-task" id="{anchor}"><summary id="co-guide-{anchor}">{safe_title}</summary><div class="co-manual-detail"><h3>{safe_title}</h3>{render(sections[title])}</div></details>')
        output.append('</section>')
    output.append('</div></div>')
    digest = hashlib.sha256(source.encode("utf-8")).hexdigest()
    return "<!-- USER_MANUAL.md sha256: " + digest + " -->\n" + "\n".join(output) + "\n"


root = Path(__file__).resolve().parent.parent
source = (root / "USER_MANUAL.md").read_text(encoding="utf-8")
target = root / "admin/includes/manuals/checkout_offer.html"
target.parent.mkdir(parents=True, exist_ok=True)
target.write_text(build(source), encoding="utf-8", newline="\n")
print("Admin manual generated: " + str(target))
