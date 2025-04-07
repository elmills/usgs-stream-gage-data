#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Python script to convert a GitHub-style README.md (with specific metadata conventions)
into a WordPress plugin repository-compatible readme.txt file.

Usage:
    python convert_readme.py --input README.md --output readme.txt
"""

import re
import argparse
import os
from collections import OrderedDict

# --- Configuration ---

# Section mapping from GitHub README (lowercase) to WordPress readme.txt key
# Order here doesn't matter, output order is controlled later.
SECTION_MAPPING = {
    'description': 'description',
    'features': 'description',  # Appends to description
    'installation': 'installation',
    'usage': 'usage',
    'frequently asked questions': 'faq',
    'faq': 'faq',
    'changelog': 'changelog',
    'upgrade notice': 'upgrade_notice',
    'screenshots': 'screenshots',
    'license': 'license',
    'support': 'other_notes',  # Example: Map 'Support' to 'Other Notes'
    # Add more mappings as needed
}

# Standard order for sections in the final readme.txt
SECTION_ORDER = [
    'description',
    'installation',
    'usage',
    'faq',
    'screenshots',
    'changelog',
    'upgrade_notice', # Usually last or near last
    'license',
    'other_notes' # Catch-all at the end
]

# Standard titles for WordPress readme.txt sections
SECTION_TITLES = {
    'description': 'Description',
    'installation': 'Installation',
    'usage': 'Usage',
    'faq': 'Frequently Asked Questions',
    'screenshots': 'Screenshots',
    'changelog': 'Changelog',
    'upgrade_notice': 'Upgrade Notice',
    'license': 'License',
    'other_notes': 'Other Notes' # Catch-all title
}

# --- Helper Functions ---

def create_contributor_slug(author_name):
    """
    Create a WordPress.org-compatible contributor slug from a name.
    Handles names like "John Doe (Example Inc.)" -> "johndoe"

    Args:
        author_name (str): The author name.

    Returns:
        str: The contributor slug (lowercase, alphanumeric).
    """
    if not author_name:
        return ''
    # Remove any text in parentheses or brackets
    name = re.sub(r'\s*[\(\[].*?[\)\]]\s*', '', author_name)
    # Trim whitespace
    name = name.strip()
    # Convert to lowercase
    slug = name.lower()
    # Replace spaces and underscores with nothing
    slug = slug.replace(' ', '').replace('_', '')
    # Remove any remaining non-alphanumeric characters
    slug = re.sub(r'[^a-z0-9]', '', slug)
    return slug

# --- Formatting Functions ---

def format_general_content(content):
    """
    Format general content: lists, basic emphasis, remove code fences.
    Keeps Markdown links intact.

    Args:
        content (str): The raw Markdown content for the section.

    Returns:
        str: Formatted content for readme.txt.
    """
    lines = content.splitlines()
    output_lines = []
    in_code_block = False

    for line in lines:
        trimmed_line = line.strip()

        # Handle code blocks (```) - Keep content, remove fences
        if trimmed_line.startswith('```'):
            in_code_block = not in_code_block
            continue  # Skip the fence line

        if in_code_block:
            # Option 1: Keep as is (simplest)
            output_lines.append(line)
            # Option 2: Indent with a tab (classic readme style)
            # output_lines.append("\t" + line)
            continue

        # Handle Headings (H3 -> bold, H4+ leave as is)
        line = re.sub(r'^###\s+(.*?)\s*$', r'**\1**', line)

        # Handle Lists
        # Ordered lists (1., 2.) - Keep numbering
        line = re.sub(r'^\s*(\d+)\.\s+', r'\1. ', line)
        # Unordered lists (-, *, +) -> Convert to *
        line = re.sub(r'^\s*[-*+]\s+', '* ', line)

        # Handle Inline Code (`code`) - Remove backticks
        line = line.replace('`', '')

        # Handle horizontal rules (---, ***, ___), remove them
        if re.match(r'^(\s*[-*_]\s*){3,}$', trimmed_line):
            continue # Skip horizontal rule lines

        # Keep Markdown links [text](url) as they are.

        output_lines.append(line)

    return "\n".join(output_lines).strip()

def format_installation(content):
    """
    Format Installation section (expects numbered list primarily).

    Args:
        content (str): Raw Markdown content.

    Returns:
        str: Formatted content.
    """
    # Use general formatting first to handle potential code blocks etc.
    content = format_general_content(content)
    # Primarily ensures lists are formatted correctly by general formatter.
    return content

def format_faq(content):
    """
    Format FAQ section (expects H3 for questions).
    Converts '### Question' to '**Question:**' followed by the answer.

    Args:
        content (str): Raw Markdown content.

    Returns:
        str: Formatted FAQ content.
    """
    lines = content.splitlines()
    output_lines = []
    current_question = None
    current_answer_lines = []

    for line in lines:
        # Check for H3 as a question marker
        match = re.match(r'^###\s+(.*?)\s*$', line)
        if match:
            # If we were processing a previous Q&A, add it to output
            if current_question is not None:
                output_lines.append(f"**{current_question.strip()}:**")
                # Format the collected answer lines
                formatted_answer = format_general_content("\n".join(current_answer_lines))
                output_lines.append(formatted_answer.strip())
                output_lines.append('') # Add a blank line
            # Start the new question
            current_question = match.group(1).strip()
            current_answer_lines = []
        elif current_question is not None:
            # Append line to the current answer
            current_answer_lines.append(line)
        else:
            # Content before the first question (if any), format generally
            # Avoid adding blank lines here unless intended
            if line.strip():
                 output_lines.append(format_general_content(line))

    # Add the last Q&A pair
    if current_question is not None:
        output_lines.append(f"**{current_question.strip()}:**")
        formatted_answer = format_general_content("\n".join(current_answer_lines))
        output_lines.append(formatted_answer.strip())

    return "\n".join(output_lines).strip()

def format_changelog(content):
    """
    Format Changelog section.
    Converts '### [Version]' or '### Version' to '= Version ='
    Keeps list items under types.

    Args:
        content (str): Raw Markdown content.

    Returns:
        str: Formatted changelog content.
    """
    lines = content.splitlines()
    output_lines = []
    in_code_block = False

    # Remove introductory sentence like "All notable changes..."
    if lines and 'all notable changes' in lines[0].lower():
        lines.pop(0)
        # Remove potential blank line after it
        if lines and not lines[0].strip():
            lines.pop(0)

    for line in lines:
        trimmed_line = line.strip()

        # Handle code blocks within changelog entries
        if trimmed_line.startswith('```'):
            in_code_block = not in_code_block
            continue # Skip the fence line
        if in_code_block:
            output_lines.append(line) # Keep code block content as is
            continue

        # Version Heading (H3) -> = Version =
        # Matches ### Version, ### [Version], ### Version - YYYY-MM-DD etc.
        match_version = re.match(r'^###\s+(?:\[?([\d\.\-a-zA-Z]+)\]?.*?)\s*$', line)
        if match_version:
            version = match_version.group(1).strip()
            # Add spacing before new version if needed
            if output_lines and output_lines[-1].strip():
                output_lines.append('')
            output_lines.append(f"= {version} =")
            # Add blank line after version heading unless next line is blank
            if len(lines) > lines.index(line) + 1 and lines[lines.index(line) + 1].strip():
                 output_lines.append('')
            continue # Skip further processing for this line

        # Change Type Heading (H4) -> Ignore for now, rely on list items
        match_type = re.match(r'^####\s+(.*?)\s*$', line)
        if match_type:
            # type_heading = match_type.group(1).strip()
            # Optional: output_lines.append(f"**{type_heading}**")
            continue # Skip the H4 line itself

        # List items (-, *, +) -> * Item
        match_item = re.match(r'^\s*[-*+]\s+(.*)', line)
        if match_item:
            item = match_item.group(1).strip()
            # Remove inline code backticks from list item
            item = item.replace('`', '')
            output_lines.append(f"* {item}")
            continue # Skip further processing

        # Other non-empty lines (potentially descriptions under versions)
        if trimmed_line:
            # Remove inline code backticks
            line_cleaned = line.replace('`', '')
            output_lines.append(line_cleaned.strip())
            continue

        # Keep intentionally blank lines if they exist between list items or paragraphs
        # (This logic might need refinement based on desired spacing)
        # if not trimmed_line and output_lines and output_lines[-1].strip():
        #      output_lines.append('')


    # Clean up potential excessive blank lines at the end
    while output_lines and not output_lines[-1].strip():
        output_lines.pop()

    return "\n".join(output_lines).strip()


def format_screenshots(content):
    """
    Format Screenshots section.
    Converts Markdown image syntax or simple lists of descriptions
    into the standard '1. Screenshot description.' format.

    Args:
        content (str): Raw Markdown content.

    Returns:
        str: Formatted screenshots content.
    """
    lines = content.splitlines()
    output_lines = []
    screenshot_index = 1

    for line in lines:
        line = line.strip()
        if not line:
            continue

        description = None
        # Try to extract description from Markdown image alt text: ![Description](url)
        match_img = re.match(r'^!\[(.*?)\]\(.*?\)', line)
        if match_img:
            description = match_img.group(1).strip()

        # Handle simple list items: - Description or * Description
        match_list = re.match(r'^\s*[-*+]\s+(.*)', line)
        if not description and match_list:
            description = match_list.group(1).strip()

        # Handle plain text lines as descriptions (if not already matched)
        # Avoid adding headings or blank lines
        if not description and not re.match(r'^#|^\s*$', line):
            description = line

        # Add the formatted line if a description was found
        if description:
            output_lines.append(f"{screenshot_index}. {description}")
            screenshot_index += 1

    return "\n".join(output_lines)

def generate_upgrade_notice(changelog_content, stable_tag):
    """
    Generate a basic Upgrade Notice based on the latest changelog entry.

    Args:
        changelog_content (str): Formatted changelog content.
        stable_tag (str): The plugin's stable tag.

    Returns:
        str: Formatted upgrade notice.
    """
    notice_lines = []
    # Find the first version heading (= x.y.z =) and its content
    match = re.search(r'^= ([\d\.\-a-zA-Z]+) =\n(.*?)(?=\n^= |\Z)', changelog_content, re.MULTILINE | re.DOTALL)

    if match:
        latest_version = match.group(1).strip()
        latest_changes = match.group(2).strip().lower() # Lowercase for keyword search

        notice_lines.append(f"= {latest_version} =")

        # Create a concise summary
        summary_parts = [f"Update to version {latest_version}."]
        if 'fix' in latest_changes or 'resolve' in latest_changes:
            summary_parts.append("Includes bug fixes.")
        if 'add' in latest_changes or 'enhance' in latest_changes or 'improve' in latest_changes:
            summary_parts.append("Includes improvements and/or new features.")
        if 'secur' in latest_changes:
            summary_parts.append("Includes security enhancements.")
        if 'deprecate' in latest_changes or 'remov' in latest_changes:
            summary_parts.append("Includes important updates or removal of features.")

        # Join unique summary parts
        summary = " ".join(list(OrderedDict.fromkeys(summary_parts))) # Keep order, remove duplicates
        notice_lines.append(summary + " See changelog for details.")

    else:
        # Fallback if no version found in changelog
        notice_lines.append(f"= {stable_tag} =")
        notice_lines.append("General improvements and bug fixes.")

    return "\n".join(notice_lines).strip()


# --- Core Conversion Class/Logic ---

class ReadmeConverter:
    """Handles the conversion process."""

    def __init__(self, github_content):
        """
        Initialize the converter.

        Args:
            github_content (str): The raw GitHub README.md content.
        """
        # Normalize line endings to LF
        self.github_content = github_content.replace("\r\n", "\n").replace("\r", "\n")
        self.plugin_meta = {}
        self.plugin_sections = {}
        self.wordpress_content = ""

    def parse_metadata(self):
        """Parse metadata from the GitHub README."""
        # Initialize default metadata
        self.plugin_meta = {
            'name': '',
            'contributors': [],
            'tags': [],
            'requires': '5.0', # Default WP version requirement
            'tested': '',
            'requires_php': '',
            'stable': 'trunk', # Default stable tag
            'license': '',
            'license_uri': '',
            'short_description': '',
            'author': '',
            'author_uri': '',
            'donate_link': ''
        }

        # 1. Extract Plugin Name (First H1 heading)
        match_h1 = re.search(r'^#\s+(.*?)\s*$', self.github_content, re.MULTILINE)
        if match_h1:
            name = match_h1.group(1).strip()
            # Remove "Plugin" suffix if present (case-insensitive)
            self.plugin_meta['name'] = re.sub(r'\s+plugin$', '', name, flags=re.IGNORECASE)

        # 2. Extract metadata from list format (- Key: Value)
        meta_matches = re.findall(r'^-\s+([\w\s]+):\s*(.*?)\s*$', self.github_content, re.MULTILINE)
        for key_raw, value_raw in meta_matches:
            key = key_raw.strip().lower()
            value = value_raw.strip()

            # Handle potential markdown link in value, e.g., Author: [Name](URL)
            link_match = re.match(r'^\[(.*?)\]\((.*?)\)$', value)
            if link_match:
                link_text = link_match.group(1).strip()
                link_url = link_match.group(2).strip()
                # If it's Author or Plugin Name, use the text part
                if key in ['author', 'plugin name']:
                    value = link_text
                # Otherwise, prefer the URL for URIs, or keep the text
                elif key in ['author uri', 'license uri', 'donate link']:
                     value = link_url
                else:
                     value = link_text # Default to text for other linked values

            # Map keys to plugin_meta dictionary
            if key == 'plugin name': self.plugin_meta['name'] = value
            elif key in ['version', 'stable tag']: self.plugin_meta['stable'] = value
            elif key == 'requires php': self.plugin_meta['requires_php'] = value
            elif key in ['tested up to', 'tested']: self.plugin_meta['tested'] = value
            elif key == 'author':
                self.plugin_meta['author'] = value
                slug = create_contributor_slug(value)
                if slug and slug not in self.plugin_meta['contributors']:
                    self.plugin_meta['contributors'].append(slug)
            elif key == 'contributors':
                 contributors_list = [c.strip() for c in value.split(',') if c.strip()]
                 for contributor in contributors_list:
                     slug = create_contributor_slug(contributor)
                     if slug and slug not in self.plugin_meta['contributors']:
                        self.plugin_meta['contributors'].append(slug)
            elif key == 'author uri': self.plugin_meta['author_uri'] = value
            elif key == 'license': self.plugin_meta['license'] = value
            elif key == 'license uri': self.plugin_meta['license_uri'] = value
            elif key == 'tags':
                self.plugin_meta['tags'] = [tag.strip() for tag in value.split(',') if tag.strip()]
            elif key in ['requires at least', 'requires']: self.plugin_meta['requires'] = value
            elif key == 'donate link': self.plugin_meta['donate_link'] = value
            elif key == 'short description': self.plugin_meta['short_description'] = value


        # 3. Extract Short Description (if not found in meta list)
        if not self.plugin_meta.get('short_description'):
            # Look for the first paragraph after the H1 or meta list, before the first H2.
            # Regex finds content after H1 or meta list item, until next H2 or end of file
            match_desc = re.search(r'(?:^#.*?$|^-.*?$)\s*?\n+(.*?)(?=\n##|\Z)', self.github_content, re.MULTILINE | re.DOTALL)
            if match_desc:
                potential_desc = match_desc.group(1).strip()
                # Take the first non-empty line as short description
                first_line = potential_desc.split('\n', 1)[0].strip()
                if first_line and not first_line.startswith(('#', '=', '- ', '* ')): # Avoid list items/headings
                    self.plugin_meta['short_description'] = first_line

        # Fallback: If still no short description, use the beginning of the ## Description section
        if not self.plugin_meta.get('short_description'):
            match_desc_section = re.search(r'##\s+Description\s*\n+(.*?)(?=\n\n|\n##|\Z)', self.github_content, re.IGNORECASE | re.DOTALL)
            if match_desc_section:
                full_desc = match_desc_section.group(1).strip()
                self.plugin_meta['short_description'] = full_desc.split('\n', 1)[0].strip()


        # 4. Generate Tags (if none found and short description exists)
        if not self.plugin_meta.get('tags') and self.plugin_meta.get('short_description'):
            text_for_tags = (self.plugin_meta.get('name', '') + ' ' + self.plugin_meta['short_description']).lower()
            words = re.split(r'[\s,\.\(\)-]+', text_for_tags) # Split by spaces and common punctuation
            common_words = {'the', 'a', 'an', 'is', 'in', 'on', 'for', 'to', 'of', 'and', 'with', 'plugin', 'widget', 'wordpress'}
            potential_tags = []
            for word in words:
                word = word.strip()
                if len(word) > 3 and word not in common_words and not word.isdigit():
                    potential_tags.append(word)
            # Use OrderedDict to keep order while getting unique tags
            self.plugin_meta['tags'] = list(OrderedDict.fromkeys(potential_tags))

        # Ensure we have at least one tag (fallback)
        if not self.plugin_meta.get('tags'):
             name_slug = re.sub(r'[^a-z0-9]+', '-', self.plugin_meta.get('name', 'plugin').lower()).strip('-')
             self.plugin_meta['tags'] = [name_slug if name_slug else 'plugin']

        # Limit to 5 tags
        self.plugin_meta['tags'] = self.plugin_meta['tags'][:5]

        # Ensure stable tag is set (use 'trunk' if version wasn't found)
        if not self.plugin_meta.get('stable'):
             self.plugin_meta['stable'] = 'trunk'

        # Ensure contributor list isn't empty (use author if needed)
        if not self.plugin_meta.get('contributors') and self.plugin_meta.get('author'):
             slug = create_contributor_slug(self.plugin_meta['author'])
             if slug:
                 self.plugin_meta['contributors'].append(slug)

         # Final fallback for contributor
        if not self.plugin_meta.get('contributors'):
             self.plugin_meta['contributors'] = ['pluginauthor'] # Generic fallback

    def parse_sections(self):
        """Parse sections from the GitHub README and format them."""
        # Initialize standard sections based on desired output order
        self.plugin_sections = OrderedDict((key, "") for key in SECTION_ORDER)

        # Regex to find all level 2 headings (## Heading) and their content
        # Uses re.finditer to get match objects for start/end positions
        section_matches = list(re.finditer(r'^##\s+([^\n]+)\n(.*?)(?=\n^##\s|\Z)', self.github_content, re.MULTILINE | re.DOTALL))

        current_description_parts = []

        for match in section_matches:
            section_title_raw = match.group(1).strip()
            section_content_raw = match.group(2).strip()
            section_key_lower = section_title_raw.lower()

            # Determine the target WordPress section using the mapping
            wp_section_key = SECTION_MAPPING.get(section_key_lower, 'other_notes') # Default to 'other_notes'

            formatted_content = ""
            # --- Section Formatting ---
            if wp_section_key == 'description':
                formatted_part = format_general_content(section_content_raw)
                # Add "Features" heading if this section was originally Features
                if section_key_lower == 'features':
                     current_description_parts.append(f"= {section_title_raw} =\n{formatted_part}")
                else:
                     current_description_parts.append(formatted_part)
                # Don't assign to self.plugin_sections yet, accumulate description parts

            elif wp_section_key == 'installation':
                formatted_content = format_installation(section_content_raw)
            elif wp_section_key == 'faq':
                formatted_content = format_faq(section_content_raw)
            elif wp_section_key == 'changelog':
                formatted_content = format_changelog(section_content_raw)
            elif wp_section_key == 'screenshots':
                formatted_content = format_screenshots(section_content_raw)
            elif wp_section_key == 'upgrade_notice':
                # Allow override from MD, otherwise generated later
                formatted_content = format_general_content(section_content_raw)
            elif wp_section_key in ['usage', 'license', 'other_notes']:
                 formatted_content = format_general_content(section_content_raw)
            else: # Default for any other mapped sections
                 formatted_content = format_general_content(section_content_raw)


            # Store the formatted content, handling description separately
            if wp_section_key != 'description' and formatted_content:
                 # Append if section already has content (e.g., multiple 'other_notes' sources)
                 if self.plugin_sections.get(wp_section_key):
                     # Add the original H2 title for clarity when merging into other_notes
                     self.plugin_sections[wp_section_key] += f"\n\n= {section_title_raw} =\n{formatted_content}"
                 else:
                     self.plugin_sections[wp_section_key] = formatted_content


        # Assign the accumulated description content
        if current_description_parts:
             self.plugin_sections['description'] = "\n\n".join(current_description_parts)


        # --- Post-processing and Generation ---

        # Generate Upgrade Notice if empty and changelog exists
        changelog_content = self.plugin_sections.get('changelog', '')
        stable_tag = self.plugin_meta.get('stable', 'trunk')
        if not self.plugin_sections.get('upgrade_notice') and changelog_content:
             self.plugin_sections['upgrade_notice'] = generate_upgrade_notice(changelog_content, stable_tag)

        # Ensure License section content matches metadata if empty
        license_meta = self.plugin_meta.get('license')
        license_uri_meta = self.plugin_meta.get('license_uri')
        if not self.plugin_sections.get('license') and license_meta:
             license_text = f"This plugin is licensed under the {license_meta}."
             if license_uri_meta:
                 license_text += f"\nSee: {license_uri_meta}"
             self.plugin_sections['license'] = license_text


    def build_header(self):
        """Build the header section of the WordPress readme.txt"""
        header_lines = []
        # Plugin name (ensure it's not empty)
        plugin_name = self.plugin_meta.get('name') or 'My Plugin'
        header_lines.append(f"=== {plugin_name} ===")

        # Contributors (ensure it's not empty)
        contributors = ', '.join(self.plugin_meta.get('contributors', ['pluginauthor']))
        header_lines.append(f"Contributors: {contributors}")

        # Optional fields only if they have values
        if self.plugin_meta.get('donate_link'):
            header_lines.append(f"Donate link: {self.plugin_meta['donate_link']}")

        # Tags (ensure array isn't empty)
        tags = ', '.join(self.plugin_meta.get('tags', ['plugin']))
        header_lines.append(f"Tags: {tags}")

        # WordPress version requirements
        header_lines.append(f"Requires at least: {self.plugin_meta.get('requires', '5.0')}")

        # Tested up to version
        if self.plugin_meta.get('tested'):
            header_lines.append(f"Tested up to: {self.plugin_meta['tested']}")

        # PHP version requirement
        if self.plugin_meta.get('requires_php'):
            header_lines.append(f"Requires PHP: {self.plugin_meta['requires_php']}")

        # Stable tag (should always have a value)
        header_lines.append(f"Stable tag: {self.plugin_meta.get('stable', 'trunk')}")

        # License info
        if self.plugin_meta.get('license'):
            header_lines.append(f"License: {self.plugin_meta['license']}")
        if self.plugin_meta.get('license_uri'):
            header_lines.append(f"License URI: {self.plugin_meta['license_uri']}")

        # Short description (ensure it's not empty)
        short_description = self.plugin_meta.get('short_description') or 'See description section.'
        header_lines.append(f"\n{short_description.strip()}") # Add blank line before

        return "\n".join(header_lines) + "\n" # Add trailing newline


    def build_sections(self):
        """Build all sections of the WordPress readme.txt"""
        section_content_lines = []
        # Add each non-empty section in the defined order
        for section_key in SECTION_ORDER:
            content = self.plugin_sections.get(section_key, "").strip()
            if content:
                # Use the standard title, fallback to key if somehow missing
                title = SECTION_TITLES.get(section_key, section_key.replace('_', ' ').title())
                section_content_lines.append(f"== {title} ==\n\n{content}")

        return "\n\n".join(section_content_lines) + "\n" # Add trailing newline


    def convert(self):
        """Perform the full conversion."""
        if not self.github_content:
            return ''

        self.parse_metadata()
        self.parse_sections()

        header = self.build_header()
        sections = self.build_sections()

        self.wordpress_content = header + "\n" + sections # Add extra newline between header and first section
        return self.wordpress_content.strip() + "\n" # Ensure single trailing newline


# --- Main Execution ---

def main():
    """Main function to handle script execution and arguments."""
    parser = argparse.ArgumentParser(
        description="Convert GitHub README.md to WordPress readme.txt format.",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter
    )
    parser.add_argument(
        "-i", "--input",
        required=True,
        help="Path to the input README.md file."
    )
    parser.add_argument(
        "-o", "--output",
        required=True,
        help="Path to the output readme.txt file."
    )
    args = parser.parse_args()

    input_file = args.input
    output_file = args.output

    # Validate input file exists
    if not os.path.isfile(input_file):
        print(f"Error: Input file not found at '{input_file}'")
        return 1 # Exit with error code

    try:
        # Read the input file (assume UTF-8)
        with open(input_file, 'r', encoding='utf-8') as f:
            markdown_content = f.read()

        # Perform the conversion
        converter = ReadmeConverter(markdown_content)
        readme_txt_content = converter.convert()

        # Write the output file (assume UTF-8)
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(readme_txt_content)

        print(f"Successfully converted '{input_file}' to '{output_file}'")
        return 0 # Exit successfully

    except FileNotFoundError:
        print(f"Error: Could not read input file '{input_file}'")
        return 1
    except IOError as e:
        print(f"Error writing output file '{output_file}': {e}")
        return 1
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
        # import traceback
        # traceback.print_exc() # Uncomment for detailed debugging
        return 1

if __name__ == "__main__":
    exit_code = main()
    exit(exit_code)

