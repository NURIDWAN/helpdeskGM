<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error occurred"),
 *     @OA\Property(property="data", type="null")
 * )
 * 
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="total", type="integer", example=50)
 * )
 * 
 * @OA\Schema(
 *     schema="LoginRequest",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="admin@gmail.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password")
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@gmail.com"),
 *     @OA\Property(property="phone_number", type="string", example="08123456789"),
 *     @OA\Property(property="branch_id", type="integer", example=1),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"admin"}),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 * 
 * @OA\Schema(
 *     schema="Ticket",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="T-001/SPK/JKT/01/2026"),
 *     @OA\Property(property="title", type="string", example="AC tidak dingin"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string", enum={"open", "in_progress", "resolved", "closed"}),
 *     @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}),
 *     @OA\Property(property="branch", ref="#/components/schemas/Branch"),
 *     @OA\Property(property="category", ref="#/components/schemas/TicketCategory"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="TicketCategory",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="AC & Pendingin"),
 *     @OA\Property(property="icon", type="string", example="snowflake"),
 *     @OA\Property(property="color", type="string", example="#3B82F6")
 * )
 * 
 * @OA\Schema(
 *     schema="Branch",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="JKT"),
 *     @OA\Property(property="name", type="string", example="Cabang Jakarta Pusat"),
 *     @OA\Property(property="address", type="string")
 * )
 * 
 * @OA\Schema(
 *     schema="WorkOrder",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="number", type="string", example="SPK/001/JKT/01/2026"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="status", type="string", enum={"pending", "in_progress", "done", "cancelled"}),
 *     @OA\Property(property="ticket", ref="#/components/schemas/Ticket"),
 *     @OA\Property(property="assigned_user", ref="#/components/schemas/User")
 * )
 * 
 * @OA\Schema(
 *     schema="WorkReport",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="work_order_id", type="integer"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="work_date", type="string", format="date"),
 *     @OA\Property(property="start_time", type="string"),
 *     @OA\Property(property="end_time", type="string")
 * )
 * 
 * @OA\Schema(
 *     schema="DailyRecord",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="branch_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="total_customers", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Role",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="staff"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="JobTemplate",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="AC Service"),
 *     @OA\Property(property="description", type="string", example="Standard AC Service procedure"),
 *     @OA\Property(property="priority", type="string", enum={"Low", "Medium", "High"}, example="Medium"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="checklist_items", type="array", @OA\Items(type="string"), example={"Check Freon", "Clean Filter"})
 * )
 *
 * @OA\Schema(
 *     schema="TicketAttachment",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="ticket_id", type="integer", example=1),
 *     @OA\Property(property="file_path", type="string", example="attachments/file.jpg"),
 *     @OA\Property(property="file_type", type="string", example="image/jpeg"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TicketReply",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="ticket_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="This is a reply"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
 * )
 */
class Schemas
{
}
