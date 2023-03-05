<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DatabaseController extends Controller
{

    public function test(Request $request, $id): JsonResponse
    {
        $query = "SELECT
            a.MSN
            , a.RequisitionId
            , a.SampleId
            , a.SampleOperationCode
            , a.StepNumber
            , a.RowNr
            , a.SubjectSampleId
        FROM
            (
            SELECT
                s.MSN
                , srf.RequisitionId
                , ss.SampleId
                , sorn.Code AS SampleOperationCode
                , sswon.StepNumber
                , ROW_NUMBER() OVER(PARTITION BY SampleId ORDER BY sswon.stepNumber) rowNr
                , ss.Id AS SubjectSampleId
            FROM    ste.SubjectSample ss (NOLOCK)
                INNER JOIN
                (    SELECT
                        sswo.SubjectSampleId
                        , MIN(sswo.StepNumber) StepNumber
                    FROM    ste.SubjectSampleWorkflowOperation sswo (NOLOCK)
                        INNER JOIN Sts.SampleOperation sor (NOLOCK)
                        ON sswo.SampleOperationId = sor.Id
                    WHERE    sor.Code = 'RECEIVE DIVISION'
                        AND sswo.ExecutionDate IS NULL
                        AND sswo.UtcExecutionDate IS NULL
                        AND sswo.IsDeleted = 0
                    GROUP BY sswo.SubjectSampleId
                ) firstRec
                ON ss.Id = firstRec.SubjectSampleId
                INNER JOIN ste.SubjectSampleWorkflowOperation sswon (NOLOCK)
                ON sswon.SubjectSampleId = firstRec.SubjectSampleId AND sswon.StepNumber > firstRec.StepNumber AND sswon.IsDeleted = 0
                INNER JOIN Sts.SampleOperation sorn (NOLOCK)
                ON sswon.SampleOperationId = sorn.Id
                INNER JOIN ste.SubjectReqForm srf (NOLOCK)
                ON srf.id = ss.SubjectReqFormId
                AND srf.IsDeleted = 0 AND srf.ReqFormStatusId = 1
                INNER JOIN stp.Site sit (NOLOCK)
                ON sit.id = srf.StudySiteId
                INNER JOIN sts.Study s (NOLOCK)
                ON s.Id = sit.StudyId
            WHERE
                ss.IsDeleted = 0
                AND ss.IsObsolete = 0
                AND s.MSN > 'F'
                AND s.IsClosed = 0
                AND sswon.IsDeleted = 0
                AND ss.SampleId = '$id'
            ) a
        WHERE
            a.rowNr IN (1, 2)";

        $response = DB::connection('sqlsrv_cerca')->select($query);
        // $test = DB::connection('sqlsrv_cerca')->getPdo();
        // dd($query);
        return response()->json($response);

        // return Redirect::route('profile.edit');
    }
}
