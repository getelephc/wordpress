/* The pinned library can clobber host callee-saved registers on regex paths.
 * Preserve the complete SysV x86_64 set around each native entry point. */
.text
.macro GUARD public, native
.globl \public
.type \public, @function
\public:
    pushq %rbp
    pushq %rbx
    pushq %r12
    pushq %r13
    pushq %r14
    pushq %r15
    subq $8, %rsp
    call \native@PLT
    addq $8, %rsp
    popq %r15
    popq %r14
    popq %r13
    popq %r12
    popq %rbx
    popq %rbp
    ret
.size \public, .-\public
.endm
GUARD guarded_ep_autop, ep_autop
GUARD guarded_ep_identity, ep_identity
GUARD guarded_ep_serialize_json, ep_serialize_json
GUARD guarded_elephc_abi_version, elephc_abi_version
GUARD guarded_elephc_init, elephc_init
GUARD guarded_elephc_shutdown, elephc_shutdown
GUARD guarded_elephc_free, elephc_free
GUARD guarded_elephc_last_error, elephc_last_error
GUARD guarded_elephc_last_status, elephc_last_status
GUARD guarded_ep_parse_json, ep_parse_json
GUARD guarded_ep_dispatch, ep_dispatch
GUARD guarded_ep_request_reset, ep_request_reset
GUARD guarded_ep_fast_absint, ep_fast_absint
GUARD guarded_ep_fast_numeric_keys, ep_fast_numeric_keys
GUARD guarded_ep_fast_text, ep_fast_text
.section .note.GNU-stack,"",@progbits
