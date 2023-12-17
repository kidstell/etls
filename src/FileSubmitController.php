<?php

class FileSubmitController
{
    function dataSubmit($request) {
        $validator = $this->validateRequest($request);
        $errors = $validator();
        if ($errors) {
            return json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
                'data' => $request,
            ]);
        }

        $uploadedFile = $request['selected_file'];
        $filePath = $uploadedFile['tmp_name'];

        $file = fopen($filePath, 'r');
        $file_headers = $this->getFileHeaders($file, $request['line1headers']);
        $scenarios = $request['scenario'];

        foreach ($scenarios as &$scenario) {
            if (empty($scenario['dst_column'])) {
                $srcIdx = $scenario['src_column'];
                $scenario['dst_column'] = $file_headers[$srcIdx];
            }
        }
        unset($scenario);

        $request['scenario'] = $scenarios;

        $processed = $this->processFile($file, $scenarios, $file_headers);
        fclose($file);

        $response = $this->prepareResponse($processed, $file_headers, $request);

        return json_encode($response);
    }

    protected function getFileHeaders($file, $useFileHeaders) {
        $file_headers = fgetcsv($file);

        if (!$useFileHeaders) {
            $columnCount = count($file_headers);
            $file_headers = array_map(function ($index) {return "Column $index";}, range(1, $columnCount));
            rewind($file);
        }
        return $file_headers;
    }

    protected function processFile($file, $scenarios, $file_headers)
    {
        $result = [];
        $lineNumber = 0;
        $dstColumnsList = array_column($scenarios, 'dst_column');
        $uniqDst = array_unique($dstColumnsList);
        $dstColumnsPos = array_flip($uniqDst);
        $columnParty = [];

        while (($line = fgetcsv($file)) !== false) {
            $result[$lineNumber] = [];

            foreach ($scenarios as $key => $scenario) {
                $srcIdx = $scenario['src_column'];
                $srcColumnName = $file_headers[$srcIdx];
                $dstColumnName = $scenario['dst_column'] ?: $file_headers[$srcIdx];
                $dstIdx = $dstColumnsPos[$dstColumnName];

                $colValue = $this->applyRegexTransform(
                    $line[$srcIdx],
                    $scenario['regex_test'] ?: '',
                    $scenario['regex_action'] ?: ''
                );

                $result[$lineNumber][$dstIdx] = $colValue;

                if (!isset($columnParty[$dstColumnName])) {
                    $columnParty[$dstColumnName] = [];
                }

                if (!in_array($srcColumnName, $columnParty[$dstColumnName])) {
                    $columnParty[$dstColumnName][] = $srcColumnName;
                }
            }

            $lineNumber += 1;
        }

        return ['column_matches' => $columnParty, 'result' => $result];
    }

    protected function prepareResponse($processed, $file_headers, $request) {
        $response = [];
        $result = $processed['result'];

        $dstColumns = array_unique(array_column($request['scenario'], 'dst_column'));

        if (count($result) > 0) {
            array_unshift($result, $dstColumns);
            $dstFile = $this->writeToCsv($result, '_' . uniqid() . "_" . $request['selected_file']['name']);

            $src_columns = [];
            foreach ($request['scenario'] as $scenario) {
                $src_columns[] = $file_headers[$scenario['src_column']];
            }

            $response = [
                'dstFile' => $dstFile,
                'download' => $dstFile,
                'src_columns' => $src_columns,
                'dst_columns' => $dstColumns,
                'rows' => count($result) - 1,
                'preview' => array_slice($result, 1, 10),
                'dst_matching' => $processed['column_matches']
            ];
        }

        return [
            'success' => true,
            'message' => 'Data successfully validated and processed.',
            'data' => $response,
        ];
    }

    function applyRegexTransform($value, $regexTest, $regexReplace)
    {
        if ($regexTest && preg_match('/' . $regexTest . '/', $value)) {
            if ($regexReplace) {
                $value = preg_replace('/' . $regexTest . '/', $regexReplace, $value);
            }
        }

        return $value;
    }

    function writeToCsv(array $data, $filename = 'output.csv', $delimiter = ',') {
        // Create a temporary file
        $tempFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        // Open the temporary file for writing
        $file = fopen($tempFilePath, 'w');

        // Write each row to the CSV file
        foreach ($data as $row) {
            fputcsv($file, $row, $delimiter);
        }

        // Close the file
        fclose($file);

        $destinationDirectory = 'csv/';
        if (!is_dir($destinationDirectory)) {
            mkdir($destinationDirectory, 0777, true);
        }

        // Move the temporary file to the desired storage location
        $destinationPath = 'csv/' . uniqid() . $filename;
        file_put_contents($destinationPath, file_get_contents($tempFilePath));

        // Close and remove the temporary file
        fclose($tempFile);

        return $destinationPath;
    }

    protected function validateRequest($request)
    {
        $rules = [
            'selected_file' => 'required',
            'field_separator' => 'required|string|size:5',
            // 'scenario.*.src_column' => 'required|string',
            // 'scenario.*.dst_column' => 'sometimes|nullable|string',
            // 'scenario.*.regex_test' => 'sometimes|nullable|string',
            // 'scenario.*.regex_action' => 'sometimes|nullable|string',
            'line1headers' => 'required|nullable',
            'scenario' => 'required|array|min:1',
        ];

        $messages = [
            'selected_file.required' => 'File is required. Please provide a data file.',
            'field_separator.required' => 'Only comma-separated values can be processed for now.',
            'scenario.*.src_column.required' => 'Select the column for this scenario(:index) operation',
        ];

        return function () use ($request, $rules, $messages) {
                $errors = [];
                foreach ($rules as $key => $rule) {
                    if (!isset($request[$key])) {
                        $errors[$key][] = $messages["$key.required"];
                    }
                }
                return count($errors) > 0 ? $errors : false;
            };
    }
}
