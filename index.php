<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/vendors/bootstrap-5.3.2/dist/css/bootstrap.min.css">
    <title>Flat File ETL demo</title>

    <style>
        fieldset {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px auto;
        }

        #scenarioList {
            counter-reset: scenarios;
        }

        .list-item::before {
            counter-increment: scenarios;
            content: "Scenario " counter(scenarios);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">ETL</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Flat File ETL</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div class="container mt-5 pt-5">
        <div class="row d-flex justify-content-center">
            <div class="col-9 border border-2 p-4">
                <div id="etlapp" class="">
                    <figure>
                        <blockquote class="display-1 blockquote">
                            <b>Flat File ETL</b>
                        </blockquote>
                        <figcaption class="blockquote-footer">Allows you to trnsform and manage data from flat files</figcaption>
                    </figure>


                    <fieldset>
                        <div class="mb-3">
                            <label for="selected_file" class="form-label">Select File</label>
                            <input class="form-control form-control-sm c2g" type="file" name="selected_file" id="selected_file">
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Configurations</legend>
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <label for="field_separator" class="form-label">Field Separator</label>
                                <select class="form-select c2g" name="field_separator" id="field_separator">
                                    <option value="comma">, (comma)</option>
                                    <!-- <option value="space"> (space)</option>
                                    <option value="pipe">| (pipe)</option> -->
                                </select>
                                <div id="filed_separator_help" class="form-text">Select separator(s)</div>
                            </div>

                            <div class="col-sm-12 col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input c2g" type="checkbox" name="line1headers" id="line1headers" checked>
                                    <label class="form-check-label" for="line1headers">Use first row as headers</label>
                                    <!-- <div id="line1headers_help" class="form-text">&nbsp; Does your flat file have headers</div> -->
                                </div>
                            </div>

                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Scenario Manager</legend>
                        <div id="scenarioList">
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <button id="addScenario" class="btn btn-primary" type="button">Create new scenario</button>
                        </div>
                    </fieldset>



                    <fieldset>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-center">
                                    <button type="button" name="generate" id="generate" class="btn btn-primary">Generate New Report</button>
                                </div>
                            </div>
                        </div>
                    </fieldset>


                    <fieldset>
                        <legend>Report</legend>
                        <div class="row">
                            <div class="col-12">
                                <div id="reportSummary"></div>
                                <table id="resultTable" class="table table-striped">

                                </table>
                            </div>
                        </div>
                    </fieldset>

                </div>
            </div>
        </div>
    </div>


    <script src="assets/vendors/jquery/jquery-3.7.1.min.js"></script>
    <script src="assets/vendors/bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendors/PapaParse-5.0.2/papaparse.min.js"></script>

    <script>
        original_file_headers = default_file_headers = file_headers = [];
        file = null;

        function getScenarioColumns() {
            ops = '';
            for (var i = 0; i < file_headers.length; i++) {
                ops += '<option value="`${file_headers[i]}`">`${file_headers[i]}`</option>';
            }
        }

        $(document).ready(function() {
            scenarioTracker = 0;

            $('#selected_file').change(function(evt) {
                const fileInput = document.getElementById('selected_file');

                // Check if any file is selected
                if (fileInput.files.length > 0) {
                    // Get the first file in the list
                    const file = fileInput.files[0];

                    // Use FileReader to read the contents of the file
                    const reader = new FileReader();

                    reader.onload = function(event) {
                        // Read the contents of the file and get the headers
                        const contents = event.target.result;
                        const lines = contents.split('\n');
                        var papa = Papa.parse(lines[0]);
                        original_file_headers = file_headers = papa.data[0];
                        default_file_headers = Array.from({
                            length: file_headers.length
                        }, (_, index) => `Column ${index + 1}`);
                        if ($('#line1headers:checked').length < 1) {
                            file_headers = default_file_headers
                        }
                    };

                    // Read the file as text
                    reader.readAsText(file);
                }
            });

            $('#line1headers').change(function(evt) {
                if (!$(this).is(":checked")) {
                    file_headers = default_file_headers
                } else {
                    file_headers = original_file_headers
                }
            });

            $('#addScenario').click(function(evt) {
                evt.preventDefault();
                tmp = `
                    <fieldset class="scenarioBox">
                        <div class="row">
                            <div class="col-12 d-flex justify-content-between">
                                <div class="h6 scenario-title list-item"></div>
                                <div><a href="" class="deleteScenario text-danger">Delete</a></div>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-2">
                                <label for="scenario_src_field_" class="form-label">Select Column</label>
                                <select class="form-select c2g" name="scenario_src_field" id="scenario_src_field_">
                                    __scenario_src_field_options__
                                </Select>
                            </div>
                            <div class="col-sm-12 col-md-6 mb-2">
                                <label for="scenario_dst_field_" class="form-label">Destination Column</label>
                                <input type="text" class="form-control c2g" name="scenario_dst_field" id="scenario_dst_field_">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-2">
                                <label for="scenario_test_regex_" class="form-label">Test Regex Condition</label>
                                <input type="text" class="form-control c2g" name="scenario_test_regex" id="scenario_test_regex_">
                            </div>
                            <div class="col-sm-12 col-md-6 mb-2">
                                <label for="scenario_action_regex_" class="form-label">Transform Regex</label>
                                <input type="email" class="form-control c2g" name="scenario_action_regex" id="scenario_action_regex_">
                            </div>
                        </div>
                    </fieldset>`

                optionMarkup = '';
                // optionMarkup = file_headers.map(header => `<option value="${header}">${header}</option>`).join('');
                // optionMarkup = file_headers.map((header, index) => `<option value="${index}">${header}</option>`).join('');
                optionMarkup = file_headers.map((header, index) => `<option value="${index}">${header}</option>`).join('');
                tmp = tmp.replace('__scenario_src_field_options__', optionMarkup);

                // boxCount = $('.scenarioBox').length
                boxCount = scenarioTracker;
                scenarioTracker += 1;
                tmp = tmp.replaceAll('scenario_src_field_', `scenario_src_column_${boxCount}`);
                tmp = tmp.replaceAll('scenario_src_field', `scenario[${boxCount}][src_column]`);
                tmp = tmp.replaceAll('scenario_dst_field_', `scenario_dst_column_${boxCount}`);
                tmp = tmp.replaceAll('scenario_dst_field', `scenario[${boxCount}][dst_column]`);
                tmp = tmp.replaceAll('scenario_test_regex_', `scenario_test_${boxCount}`);
                tmp = tmp.replaceAll('scenario_test_regex', `scenario[${boxCount}][regex_test]`);
                tmp = tmp.replaceAll('scenario_action_regex_', `scenario__${boxCount}`);
                tmp = tmp.replaceAll('scenario_action_regex', `scenario[${boxCount}][regex_action]`);


                template = $(tmp);
                $('#scenarioList').append(template)
            });

            $(document).on('click', '#scenarioList .deleteScenario', function(evt) {
                evt.preventDefault();

                console.log('Delete link clicked');

                var scenarioBox = $(this).closest(".scenarioBox");

                if (scenarioBox.length > 0) {
                    scenarioBox.remove();
                }
            });

            function extractInputValues(inputArray) {
                var values = {};
                formData = new FormData();

                // Loop through each element in the input array
                inputArray.each(function() {
                    var input = $(this);
                    var inputType = input.attr('type');
                    var inputValue;

                    // Determine the input type and extract the value accordingly
                    switch (inputType) {
                        case 'file':
                            isMultiple = input.attr('multiple');
                            if (isMultiple) {
                                inputValue = Array.from(input[0].files)
                            } else {
                                inputValue = input[0].files[0]
                            }
                            // inputValue = input.val();
                            break;
                        case 'checkbox':
                            inputValue = input.is(':checked');
                            break;
                        case 'radio':
                            if (input.is(':checked')) {
                                inputValue = input.val();
                            }
                            break;
                        default:
                            inputValue = input.val();
                    }

                    // Store the value in the 'values' object with the input name as the key
                    key = input.attr('name');
                    values[key] = inputValue;
                    formData.append(key, inputValue);
                });

                return formData;
            }

            $('#generate').click(function(evt) {
                var inputArray = $('.c2g'); // Replace this with your actual jQuery input array
                // console.log(inputArray);
                var formData = extractInputValues(inputArray);
                $.ajax({
                    type: 'POST',
                    url: 'api/upload.php',
                    data: formData,
                    processData: false, // Prevent jQuery from processing the data
                    contentType: false, // Prevent jQuery from setting the content type
                    success: function(response) {
                        console.log('Data submitted successfully:', response);
                        handleResponse(response.data);
                    },
                    error: function(error) {
                        console.error('Error submitting data:', error);
                    }
                });
            });

            function handleResponse(response) {
                // Create the table header HTML markup
                const headerHTML = response.dst_columns.map(column => `<th>${column}</th>`).join('');
                const theadHTML = `<thead><tr>${headerHTML}</tr></thead>`;

                // Create the table body HTML markup
                const tbodyHTML = response.preview.map(rowData => {
                    const rowHTML = rowData.map(cell => `<td>${cell}</td>`).join('');
                    return `<tr>${rowHTML}</tr>`;
                }).join('');

                const footerHTML = `<tfoot><tr><td colspan="${response.dst_columns.length}">Previewing 10 of ${response.rows}</td></tr></tfoot>`;

                const tableHtml = `${theadHTML}<tbody>${tbodyHTML}</tbody>${footerHTML}`;
                $('#resultTable').html(tableHtml);
                
                distinctSrc = Array.from(new Set(response.src_columns));
                distinctDst = Array.from(new Set(response.dst_columns));
                const statsHTML = `<div>Processed ${response.rows} rows | ${distinctSrc.length} source columns | ${distinctDst.length} destination columns</div><div><a href="${response.download}" download="${response.dstFile}">Download File</a></div>`;

                $('#reportSummary').html(statsHTML);
            }



        });
    </script>

</body>

</html>