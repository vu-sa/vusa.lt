// Every `v-html` names where its HTML is sanitized, so a new sink cannot skip the question.
// Stored HTML is sanitized on write (AGENTS.md, "Stored HTML is sanitized on write").

const MARKER = /^\s*v-html:\s*\S/;

function isLintDirective(comment) {
  return /^\s*eslint-/.test(comment.value);
}

export const htmlSourcePlugin = {
  rules: {
    'require-comment': {
      meta: {
        type: 'problem',
        schema: [],
        messages: {
          missing: 'Put `<!-- v-html: <where this HTML is sanitized> -->` right before this element.',
        },
      },
      create(context) {
        const { sourceCode } = context;
        const comments = sourceCode.ast.templateBody?.comments ?? [];

        function precedingMarker(element) {
          let end = element.range[0];

          for (let index = comments.length - 1; index >= 0; index -= 1) {
            const comment = comments[index];

            if (comment.range[1] > end) {
              continue;
            }

            if (sourceCode.text.slice(comment.range[1], end).trim() !== '') {
              return null;
            }

            if (!isLintDirective(comment)) {
              return comment;
            }

            end = comment.range[0];
          }

          return null;
        }

        function check(node) {
          const marker = precedingMarker(node.parent.parent);

          if (!marker || !MARKER.test(marker.value)) {
            context.report({ node, messageId: 'missing' });
          }
        }

        return sourceCode.parserServices?.defineTemplateBodyVisitor
          ? sourceCode.parserServices.defineTemplateBodyVisitor({
              'VAttribute[directive=true][key.name.name="html"]': check,
              // `:innerHTML` is v-html under another name.
              'VAttribute[directive=true][key.name.name="bind"]'(node) {
                if (/^(?:inner|outer)html$/i.test(node.key.argument?.name ?? '')) {
                  check(node);
                }
              },
            })
          : {};
      },
    },
  },
};
