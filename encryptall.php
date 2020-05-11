<html>
    <head>

    </head>
    <body>
        <form method="post">
            <label>Directory</label>
            <input style="width: 600px" type="text" name="encryptdir" value="<?php echo @$_POST['encryptdir'] ?>" required="true"/>
            <input type="submit" value="Encrypt" /> 
        </form>
    </body>
</html>
<?php
if (isset($_POST['encryptdir'])) {

    $dir = $_POST['encryptdir'];

    $files = array();

    if (is_file($dir)) {
        $files[] = $dir;
    } else if (is_dir($dir)) {

        $recurdir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($recurdir as $filename => $path_object) {
            if (is_file($filename) && pathinfo($filename, PATHINFO_EXTENSION) == 'php') {
                $files[] = $filename;
            }
        }
    }

    foreach ($files as $file) {
        $filecontent = cleanPHPFileForEncrypt($file);

        if (!strpos($filecontent, '__COMPILER_HALT_OFFSET__')) {
            echo "Encrypting $file... ";
            EncryptPHPFileFullPHP($file, $filecontent);
            echo "Done!<br/>";
        } else {
            echo "$file is already encrypted<br/>";
        }
    }
}

function EncryptPHPFileFullPHP($filename, $filecontent) {
    $_template = '<?php $fp = fopen(__FILE__, "r");fseek($fp, __COMPILER_HALT_OFFSET__);';
    $code = $filecontent;
    $_code = base64_encode($code);
    $gz_code = gzdeflate($_code);
    $sha_code = sha1($_code);
    $sha_code_length = strlen($sha_code);
    $f1 = 'if (!function_exists("YiunIUY76bBhuhNYIO8")) {
                    function YiunIUY76bBhuhNYIO8($g, $b = 0) {
                    $a = $g;
                    $d = array(0, ' . $sha_code_length . ', 40);      
                    $f = substr($a,$d[1]); 
                    return($f);
                }
            }            
            eval(base64_decode(gzinflate(YiunIUY76bBhuhNYIO8($fps))));';
    $_f1 = gzdeflate($f1);
    $f2 = '$fps = stream_get_contents($fp);';
    $_f2 = gzdeflate($f2);
    $_template .= "eval(gzinflate('$_f2'));;eval(gzinflate('$_f1'));__halt_compiler();";
    $result = $_template . $sha_code . $gz_code;
    file_put_contents($filename, $result);
}

function removeComments($content) {

    $content = preg_replace('/<!--(.|\s)*?-->/i', "", $content);
    $content = str_replace('<?php', '', $content);
    $content = str_replace('?>', '', $content);
    $content = str_replace('<?', '', $content);
//Remove multiline comment
    $content = preg_replace('!/\*.*?\*/!s', '', $content);
//Remove blank lines
    $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);
    $content = preg_replace('/\n\s*\n/', "\n", $content);

    return $content;
}

function convertHtmlTagsToPhp($fileurl) {

    $newcontent = "";

    $file = fopen($fileurl, "r");

    while (!feof($file)) {
        $line = fgets($file);
        $result = array();
        preg_match('/(?<!\'|\")<(?!\?)(.*)>/i', $line, $result);
        if (isset($result[0])) {
            $r = str_replace("'", "", $result[0]);
            $line = "echo '{$r}';";
        }
        $newcontent .= $line;
    }

    fclose($file);

    $newcontent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newcontent);
    $newcontent = preg_replace('/\n\s*\n/', "\n", $newcontent);

    return $newcontent;
}

function cleanPHPFileForEncrypt($fileurl) {

    $content = file_get_contents($fileurl);

    file_put_contents($fileurl, removeComments($content));

    $newcontent = convertHtmlTagsToPhp($fileurl);

    return $newcontent;
}

//Useless now
function stringInsert($str, $pos, $insertstr) {
    if (!is_array($pos)) {
        $pos = array($pos);
    }
    $offset = -1;
    foreach ($pos as $p) {
        $offset++;
        $str = substr($str, 0, $p + $offset) . $insertstr . substr($str, $p + $offset);
    }
    return $str;
}

function doAllHtmlTagToPhp($content) {
    $i = 0;
    $result = array();
    preg_match_all('/(?<!\'|\"|\\/)<(?:\/)?[a-z]+(?:[^>]+(?<!-)>)(?!\'|\")/i', $content, $result);
    $content = preg_replace('/<!--.*?-->/i', "", str_replace('?>', '', str_replace('<?', '', str_replace('<?php', '', $content))));
    foreach ($result as $value) {
        foreach ($value as $v) {
            $v1 = stringInsert($v, strlen($v) - 1, " $i");
            $replace = "echo '$v1';";
            $content = str_replace($v, $replace, $content);
            $i++;
        }
    }
    return $content;
}
