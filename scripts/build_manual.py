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
    sections = list(zip(parts[1::2], parts[2::2]))
    if not sections or sections[0][0] != "Contents":
        raise ValueError("Manual must begin with Contents and task sections")
    tasks = sections[1:]
    quick = next((task for task in tasks if task[0] == "Create your first offer"), None)
    if quick is None:
        raise ValueError("Manual quick-start section is missing")
    introduction = parts[0].strip().split("\n\n", 2)
    if len(introduction) != 3:
        raise ValueError("Manual introduction is incomplete")
    output = [render("\n\n".join(introduction[:2]))]
    output.append('<details class="co-manual-about"><summary>About this guide</summary>' + render(introduction[2]) + '</details>')
    output.append('<a class="co-manual-browse" href="#co-manual-navigation">Browse all tasks ↓</a>')
    navigation = '<nav class="co-manual-nav" id="co-manual-navigation" tabindex="-1" aria-label="Manual tasks"><h3>Find a task</h3>' + render(sections[0][1]) + '</nav>'
    output.append('<div class="co-manual-search" hidden><label for="co-manual-query">Search this guide</label><input id="co-manual-query" type="search" placeholder="Try pricing, stock or alignment" aria-controls="co-manual-tasks"><p role="status" aria-live="polite"></p></div>')
    output.append('<div id="co-manual-tasks">')
    for title, body in [quick] + [task for task in tasks if task != quick]:
        anchor = slugify(title, "-")
        safe_title = html.escape(title)
        if (title, body) == quick:
            output.append(f'<section class="co-manual-quick" id="{anchor}" tabindex="-1"><span class="co-manual-kicker">Quick start · your first offer</span><h3>{safe_title}</h3>{render(body)}</section>')
            output.append(navigation)
        else:
            output.append(f'<details class="co-manual-task" id="{anchor}"><summary><h3>{safe_title}</h3><span>View guide</span></summary><div class="co-manual-detail">{render(body)}</div></details>')
    output.append('</div>')
    digest = hashlib.sha256(source.encode("utf-8")).hexdigest()
    return "<!-- USER_MANUAL.md sha256: " + digest + " -->\n" + "\n".join(output) + "\n"


root = Path(__file__).resolve().parent.parent
source = (root / "USER_MANUAL.md").read_text(encoding="utf-8")
target = root / "admin/includes/manuals/checkout_offer.html"
target.parent.mkdir(parents=True, exist_ok=True)
target.write_text(build(source), encoding="utf-8", newline="\n")
print("Admin manual generated: " + str(target))
