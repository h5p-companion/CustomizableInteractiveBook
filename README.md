# CustomCSS

Interactive Book is a library that combines multiple H5P content types into a single vertical layout.
It supports nested content, individual configuration for each item, and drag-and-drop reordering within the editor.

## Project status
Beta

## Badges
On some READMEs, you may see small images that convey metadata, such as whether or not all the tests are passing for the project. You can use Shields to add some to your README. Many services also have instructions for adding a badge.

## Visuals
![Example](./img/exampleCIB.png)

## Installation
Within a particular ecosystem, there may be a common way of installing things, such as using Yarn, NuGet, or Homebrew. However, consider the possibility that whoever is reading your README is a novice and would like more guidance. Listing specific steps helps remove ambiguity and gets people to using your project as quickly as possible. If it only runs in a specific context like a particular programming language version or operating system or has dependencies that have to be installed manually, also add a Requirements subsection.

## Usage
Use examples liberally, and show the expected output if you can. It's helpful to have inline the smallest example of usage that you can demonstrate, while providing links to more sophisticated examples if they are too long to reasonably include in the README.

## Host access policy architecture

Access follows a one-way responsibility chain: **Moodle → access policy → H5P**. The Moodle plugin is responsible for evaluating courses, groups, grades and activities. The Interactive Book does not know those concepts; it only applies a policy indexed by each chapter's permanent `subContentId`.

Before creating chapter runtimes, the book sends its manifest to the immediate parent window:

```json
{
  "type": "h5p-customizable-interactive-book:ready",
  "contractVersion": 1,
  "requestId": "...",
  "contentId": "...",
  "library": "H5P.CustomizableInteractiveBook",
  "chapters": [{ "id": "...", "title": "...", "position": 0, "stable": true }]
}
```

The host may answer with `h5p-customizable-interactive-book:policy`, using the same contract version, request ID and content ID, and a chapter map containing `available` and an optional plain-text `message`. Source window and origin are validated against the immediate parent. The ready message is retried during a short window.

```json
{
  "type": "h5p-customizable-interactive-book:policy",
  "contractVersion": 1,
  "requestId": "...",
  "contentId": "...",
  "required": true,
  "teacherBypass": false,
  "chapters": {
    "chapter-uuid": { "available": false, "message": "Complete the prerequisite." }
  }
}
```

If the book is not embedded, the parent origin cannot be determined, or no valid policy arrives within approximately 2.5 seconds, the book uses an allow-all policy. This keeps the content functional outside Moodle.

The restriction is pedagogical and navigational, not a content-encryption mechanism. An H5P package still contains every chapter parameter. For a blocked chapter, however, its child H5P library is not initialized, does not contribute to score, progress, state changes, completion, reset, solutions, summary or xAPI, and only an accessible plain-text placeholder is displayed.

## Support
Your own risk.

## Roadmap
No evolution plans.

## Contributing
Fork this project and send a PR to us.

## Authors and acknowledgment
- Luiz Gustavo 
- KelsonCM @ github

## License

(The MIT License)

Copyright (c) 2012-2014 Joubel AS
 
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
