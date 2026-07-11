/**
 * Splits input text into line-based chunks so that translation happens
 * per-line, keeping newlines (paragraph breaks, list structure) untouched
 * by the model. Leading markdown markers (#, -, *, >, 1., checkboxes) are
 * peeled off before translation and re-attached afterwards so headings and
 * lists keep their structure.
 */

export interface LineChunk {
  marker: string;
  content: string;
  isBlank: boolean;
}

const LEADING_MARKER_REGEX = /^(\s*(?:#{1,6}\s+|(?:[-*+])\s+(?:\[[ xX]\]\s+)?|\d+[.)]\s+|>\s*)+)/;

const MAX_SINGLE_CHUNK_LENGTH = 300;

/** Splits a paragraph line into sentence-sized chunks once it exceeds MAX_SINGLE_CHUNK_LENGTH. */
const splitIntoSentences = (text: string): string[] => {
  if (text.length <= MAX_SINGLE_CHUNK_LENGTH) return [text];

  const sentences = text.match(/[^.!?]+[.!?]+(\s+|$)|[^.!?]+$/g);
  return sentences ? sentences.map((s) => s.trim()).filter(Boolean) : [text];
};

export const splitIntoLines = (text: string): LineChunk[] =>
  text.split('\n').map((line) => {
    if (line.trim().length === 0) {
      return { marker: '', content: line, isBlank: true };
    }
    const markerMatch = line.match(LEADING_MARKER_REGEX);
    const marker = markerMatch ? markerMatch[1] : '';
    const content = marker ? line.slice(marker.length) : line;
    return { marker, content, isBlank: false };
  });

export const joinLines = (lines: string[]): string => lines.join('\n');

export { splitIntoSentences };
