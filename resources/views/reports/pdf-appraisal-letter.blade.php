<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $letter->title }} - {{ $letter->reference_no }}</title>
    <style>
        @page {
            margin: 40px 45px 50px 45px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.6;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .company-name {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .ref-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .ref-table td {
            vertical-align: top;
            font-size: 10px;
        }
        .ref-box {
            font-size: 10px;
            color: #475569;
        }
        .ref-box strong {
            color: #0f172a;
        }
        .letter-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 20px;
            text-align: center;
        }
        .content {
            font-size: 11px;
            color: #334155;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .content p {
            margin-bottom: 14px;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 25px;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
        }
        .sign-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            min-height: 110px;
        }
        .sign-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
        }
        .sign-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }
        .sign-img {
            max-height: 45px;
            max-width: 160px;
            margin: 6px 0;
            display: block;
        }
        .sign-stamp {
            font-size: 9px;
            color: #059669;
            font-weight: bold;
            margin-top: 4px;
        }
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Header / Letterhead -->
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="company-name">LOOPS HR ENTERPRISE</div>
                    <div class="company-sub">Human Resource Management & People Operations</div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="font-size: 10px; font-weight: bold; color: #2563eb;">CONFIDENTIAL & OFFICIAL</div>
                    <div style="font-size: 9px; color: #64748b;">Ref: {{ $letter->reference_no }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Reference and Addressee Meta -->
    <table class="ref-table">
        <tr>
            <td style="width: 60%;">
                <div class="ref-box">
                    <strong>To:</strong><br>
                    <span style="font-size: 12px; font-weight: bold; color: #0f172a;">{{ $employee->user->name ?? 'Employee' }}</span><br>
                    Designation: {{ $employee->designation->name ?? 'Staff' }}<br>
                    Employee ID: {{ $employee->employee_id_number ?? 'EMP-00' }}<br>
                    Department: {{ $employee->department->name ?? 'General' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="ref-box">
                    <strong>Date of Issue:</strong> {{ $letter->created_at->format('d F Y') }}<br>
                    <strong>Review Cycle:</strong> {{ $appraisal?->cycle?->name ?? 'Annual Appraisal' }}<br>
                    <strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $letter->status)) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Letter Title -->
    <div class="letter-title">{{ $letter->title }}</div>

    <!-- Letter Content Body -->
    <div class="content">
        {!! $letter->template_content !!}
    </div>

    <!-- Dual Digital Signatures Section -->
    <table class="signature-table">
        <tr>
            <!-- Management Signatory -->
            <td>
                <div class="sign-box">
                    <div class="sign-title">Authorized Management Signatory</div>
                    <div class="sign-name">{{ $letter->signatory_name ?? 'Anoj / Shehan (Executive Management)' }}</div>
                    
                    @if($letter->signatory_signature_data)
                        <img src="{{ $letter->signatory_signature_data }}" class="sign-img" alt="Management Signature">
                    @else
                        <div style="height: 35px; border-bottom: 1px solid #cbd5e1; margin-top: 10px;"></div>
                    @endif

                    @if($letter->signatory_signed_at)
                        <div class="sign-stamp">
                            &check; Digitally Verified: {{ $letter->signatory_signed_at->format('d M Y, h:i A') }}
                        </div>
                    @else
                        <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">Pending Management E-Signature</div>
                    @endif
                </div>
            </td>

            <!-- Employee Acknowledgment -->
            <td>
                <div class="sign-box">
                    <div class="sign-title">Employee Acknowledgment & Receipt</div>
                    <div class="sign-name">{{ $employee->user->name ?? 'Employee' }}</div>

                    @if($letter->employee_signature_data)
                        <img src="{{ $letter->employee_signature_data }}" class="sign-img" alt="Employee Signature">
                    @else
                        <div style="height: 35px; border-bottom: 1px solid #cbd5e1; margin-top: 10px;"></div>
                    @endif

                    @if($letter->employee_acknowledged_at)
                        <div class="sign-stamp">
                            &check; Acknowledged & Signed: {{ $letter->employee_acknowledged_at->format('d M Y, h:i A') }}
                        </div>
                    @else
                        <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">Pending Employee Acknowledgment</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        This document is an authenticated electronic record issued under the authority of Loops HR System. System Reference: {{ $letter->reference_no }} &bull; Generated on {{ now()->format('d-m-Y H:i:s') }}
    </div>

</body>
</html>
