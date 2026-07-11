/**
 * Swaps URLs/emails/HTML/code/emoji for numeric placeholders before a
 * string hits the MT model, then restores them after translation.
 * Numeric placeholders survive MarianMT's tokenizer much better than
 * alphabetic ones, which tend to get split into garbage subwords.
 *
 * Markdown links keep their `[text]` translatable and only freeze the
 * `(url)` part; images are frozen whole since alt text rarely matters.
 */

const PLACEHOLDER_BASE = 900001;

const EMOJI_PATTERN =
  '[\\u{1F1E6}-\\u{1FFFF}\\u{2600}-\\u{27BF}\\u{2B00}-\\u{2BFF}\\u{2190}-\\u{21FF}](?:\\u{FE0F})?(?:\\u{200D}[\\u{1F1E6}-\\u{1FFFF}\\u{2600}-\\u{27BF}](?:\\u{FE0F})?)*';

const PROTECT_PATTERN = new RegExp(
  [
    '```[\\s\\S]*?```', // fenced code block
    '`[^`\\n]+`', // inline code
    '!\\[[^\\]]*\\]\\([^)]+\\)', // markdown image ![alt](url) -- protected as a whole unit
    '<\\/?[a-zA-Z][a-zA-Z0-9-]*(?:\\s+[^<>]*?)?\\/?>', // html tag
    '\\(https?:\\/\\/[^\\s<>()]+\\)', // parenthesized url, e.g. the "(url)" half of a markdown link -- keeps both parens intact
    'https?:\\/\\/[^\\s<>"\')\\]]+', // absolute url
    '\\bwww\\.[^\\s<>"\')\\]]+', // www.-style url
    '[\\w.+-]+@[\\w-]+\\.[a-zA-Z]{2,}', // email
    EMOJI_PATTERN, // emoji (incl. ZWJ sequences)
  ].join('|'),
  'gu',
);

export interface ProtectedText {
  text: string;
  tokens: string[];
}

const placeholderFor = (index: number): string => String(PLACEHOLDER_BASE + index);

/** Matches a placeholder's digits even if the model inserted separators (commas/periods/spaces) between them. */
const placeholderRestoreRegex = (index: number): RegExp =>
  new RegExp(placeholderFor(index).split('').join('[,.\\s]?'));

export const protectText = (input: string): ProtectedText => {
  const tokens: string[] = [];
  const text = input.replace(PROTECT_PATTERN, (match) => {
    const index = tokens.length;
    tokens.push(match);
    return placeholderFor(index);
  });
  return { text, tokens };
};

export const restoreProtectedText = (translated: string, tokens: string[]): string => {
  const restored = tokens.reduce(
    (acc, original, index) => acc.replace(placeholderRestoreRegex(index), original),
    translated,
  );
  // The model sometimes inserts a stray space between a markdown link's "]" and
  // its restored "(url)" -- CommonMark requires them adjacent to form a valid link.
  return restored.replace(/\]\s+\(/g, '](');
};
