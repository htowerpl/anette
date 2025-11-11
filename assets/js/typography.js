document.addEventListener("DOMContentLoaded", () => {
  const TARGETS = ["p", "li", "blockquote"];
  const SINGLE_REGEX = /(^|[\s\u00A0])([AaIiOoUuWwZz])([\s\u00A0]+)/g;

  function protectText(text) {
    return text.replace(SINGLE_REGEX, (match, before, letter) => {
      return `${before}${letter}\u00A0`;
    });
  }

  TARGETS.forEach((selector) => {
    document.querySelectorAll(selector).forEach((element) => {
      const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
      const nodes = [];
      while (walker.nextNode()) {
        nodes.push(walker.currentNode);
      }
      nodes.forEach((node) => {
        node.nodeValue = protectText(node.nodeValue);
      });
    });
  });
});
