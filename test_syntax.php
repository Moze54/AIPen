<?php
// 测试生成的 JavaScript 语法
$js = '';
$js .= '(function() {' . "\n";
$js .= '  const actionUrl = window.location.origin.replace(/\/+$/, "") + "/AIPen/Action";' . "\n";
$js .= '  console.log(actionUrl);' . "\n";
$js .= '})();' . "\n";

file_put_contents('test.js', $js);
echo "JavaScript written to test.js\n";
echo "Content:\n";
echo $js;
?>
