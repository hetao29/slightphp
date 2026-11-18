<p data-template-case="currency">${$price}</p>
<p data-template-case="native-braces"></p>
<style>.tpl-native-braces{color:#3b82f6}</style>
<script data-template-default-script>
(() => {
    function amount(){return {value:{$number}};}
    const native = `sum:${1+2}`;
    const $nativeName = "native";
    const object = {literal}{$nativeName}{/literal};
    const output = document.querySelector('[data-template-case="native-braces"]');
    if (output) output.textContent = `${amount().value} / ${native} / ${object.$nativeName}`;
})();
</script>
