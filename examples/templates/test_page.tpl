<html>
    <head>
        <title>PHPlater: Example page</title>
    </head>
    <body>
        <h1>From TestPlate.php with object that extends PHPlater</h1>
        {{ test_plate.header }}
        {{ test_plate.number_list }}
        {{ test_plate.select }}
        <hr />
        <h1>From Test.php with object that represents any object</h1>
        {{ test.number }}<br />
        {{ test.text }}
        <hr />
        <h1>String injectend into PHPlater</h1>
        {{ plain }}
        <hr />
        <h1>PHPlater object that has a template that is not in file</h1>
        {{ no_file }}
        <hr />
        <h1>PHPlater object that has all plates from array</h1>
        {{ from_array }}
        <hr />
    </body>
</html>