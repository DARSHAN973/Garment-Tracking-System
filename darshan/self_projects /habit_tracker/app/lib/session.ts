import crypto from "crypto" ;
import {prisma} from "@/app/lib/prisma" ;

const SESSION_DURATION_DAYS = 7 ;

export async function createSession(userId: string) {
    const token = crypto.randomBytes(32).toString("hex");

    const expiresAt = new Date ();
    expiresAt.setDate(expiresAt.getDate() + SESSION_DURATION_DAYS) ; 
    await prisma.session.create({
        data:{
            userId,
            token,
            expiresAt
        },
    });
    return {token , expiresAt}
}


