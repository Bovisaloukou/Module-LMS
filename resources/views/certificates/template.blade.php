<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #1a1a2e;
        }

        .certificate {
            width: 100%;
            height: 100%;
            position: relative;
            padding: 60px 80px;
            box-sizing: border-box;
        }

        .border-outer {
            border: 3px solid #16213e;
            padding: 10px;
            height: calc(100% - 120px);
            box-sizing: border-box;
        }

        .border-inner {
            border: 1px solid #0f3460;
            padding: 40px 60px;
            height: 100%;
            box-sizing: border-box;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header {
            font-size: 14px;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: #0f3460;
            margin-bottom: 10px;
        }

        .title {
            font-size: 42px;
            font-weight: 300;
            color: #16213e;
            margin: 10px 0;
            letter-spacing: 3px;
        }

        .subtitle {
            font-size: 14px;
            color: #555;
            margin: 15px 0;
        }

        .student-name {
            font-size: 32px;
            font-weight: 700;
            color: #0f3460;
            margin: 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e94560;
            display: inline-block;
        }

        .course-title {
            font-size: 20px;
            font-weight: 600;
            color: #16213e;
            margin: 20px 0 10px;
        }

        .details {
            font-size: 12px;
            color: #666;
            margin-top: 25px;
        }

        .details-row {
            margin: 5px 0;
        }

        .footer {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .footer-col {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: bottom;
        }

        .footer-line {
            border-top: 1px solid #333;
            width: 150px;
            margin: 0 auto 5px;
        }

        .footer-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-value {
            font-size: 11px;
            color: #333;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-outer">
            <div class="border-inner">
                <div class="header">Module LMS</div>
                <div class="title">Certificate of Completion</div>
                <div class="subtitle">This is to certify that</div>
                <div class="student-name">{{ $student->name }}</div>
                <div class="subtitle">has successfully completed the course</div>
                <div class="course-title">{{ $course->title }}</div>

                <div class="details">
                    <div class="details-row">Issued on {{ $certificate->issued_at->format('F j, Y') }}</div>
                </div>

                <div class="footer">
                    <div class="footer-col">
                        <div class="footer-value">{{ $certificate->issued_at->format('M d, Y') }}</div>
                        <div class="footer-line"></div>
                        <div class="footer-label">Date</div>
                    </div>
                    <div class="footer-col">
                        <div class="footer-value">{{ $certificate->certificate_number }}</div>
                        <div class="footer-line"></div>
                        <div class="footer-label">Certificate ID</div>
                    </div>
                    <div class="footer-col">
                        <div class="footer-value">Module LMS</div>
                        <div class="footer-line"></div>
                        <div class="footer-label">Platform</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
